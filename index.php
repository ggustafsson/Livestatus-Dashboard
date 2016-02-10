<!doctype html>

<!--
# livestatus-dash
# Version 0.16 beta
#
# Copyright (c) 2016, Göran Gustafsson. All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
# 1. Redistributions of source code must retain the above copyright notice,
#    this list of conditions and the following disclaimer.
#
# 2. Redistributions in binary form must reproduce the above copyright notice,
#    this list of conditions and the following disclaimer in the documentation
#    and/or other materials provided with the distribution.
#
# 3. Neither the name of the copyright holder nor the names of its contributors
#    may be used to endorse or promote products derived from this software
#    without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
# ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
# LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
# CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
# SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
# INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
# CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
# ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
-->

<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Livestatus Dashboard</title>
  <link rel="stylesheet" type="text/css" href="files/style.css">
  <script type="text/javascript" src="files/script.js"></script>
  <script type="text/javascript">
    function everySecond() {
      counter--;
      document.getElementById("counter").innerHTML = counter;
      if (counter == 0) {
        location.reload(true);
      }
    }
<?php
  include "files/config.php";

  $debug_mode = $_GET["debug"];
  if (isset($debug_mode)) {
    ini_set("display_errors", "on");
    error_reporting(E_ALL);
  } else {
    print "    var counter = ${refresh}\n";
    print "    window.setInterval(everySecond, 1000);\n";
  }
?>
  </script>
</head>
<body>
<?php
  // Prints out result from function fetch().
  function debug($title, $url, $query_hosts, $result_hosts, $query_services, $result_services) {
    $result_hosts = json_decode($result_hosts, true);
    $result_services = json_decode($result_services, true);

    print "<div class='debug'>\n";
    print "<b>${title}</b>\n";
    print "<hr>\n";
    if ($url != null) {
      print "<a href='${url}'>${url}</a>\n";
    } else {
      print "Variable 'url' is empty.\n";
    }
    print "<hr>\n";
    debug_helper("query_hosts", $query_hosts, $result_hosts);
    print "<hr>\n";
    debug_helper("query_services", $query_services, $result_services);
    print "</div>\n";
  }

  // Prints out each element of a result from function debug().
  function debug_helper($name, $query, $result) {
    if ($query != null) {
      print "<pre>\n${query}\n</pre>\n";
      print "<br>\n";
      if ($result != null) {
        // Loop through all different results.
        print "<pre>\n";
        foreach ($result as $value) {
          $elements = sizeof($value);
          // Loop through all elements in each result.
          for ($i = 0; $i < $elements; $i++) {
            print "\$result[${i}] = ${value[$i]}\n";
          }
        }
        print "</pre>\n";
      } else {
        print "<b>ERROR</b>: No results returned from Livestatus!\n";
      }
    } else {
      print "Variable '${name}' is empty.\n";
    }
  }

  // Writes query to the livestatus UNIX socket.
  function fetch($query) {
    include "files/config.php";

    $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    $connect = socket_connect($socket, $livestatus);
    socket_write($socket, $query . "\n\n");
    $result = "";
    // Read 1024 bytes at a time until received data ends.
    while ($data = socket_read($socket, 1024)) {
      $result .= $data;
    }
    socket_close($socket);

    return $result;
  }

  // Displays widget box with health of hosts and services.
  function widget($title, $url, $result_hosts, $result_services) {
    if ($result_hosts != null) {
      $result_hosts = json_decode($result_hosts, true);
      // If host(s) is not OK then it is either down or unreachable.
      $host_down = $result_hosts[0][1] + $result_hosts[0][2] + $result_hosts[0][3];
    } else {
      $host_down = -1; // Indicate that something is wrong.
    }
    $result_services = json_decode($result_services, true);

    $warning = $result_services[0][1];
    $critical = $result_services[0][2];
    $unknown = $result_services[0][3];

    if ($critical >= 1 || $host_down >= 1) {
      $widget_class = "critical";
    } elseif ($warning >= 1 || $unknown >= 1) {
      $widget_class = "warning";
    } else {
      $widget_class = "normal";
    }

    if ($url != null) {
      print "<a href='${url}' target='_blank'>\n";
    }
    print "<div class='widget ${widget_class}'>\n";
    print "<h2>${title}</h2>\n";
    print "<table>\n";
    print "<tr><td class='left'>${critical}</td><td class='right'>Critical</td></tr>\n";
    print "<tr><td class='left'>${warning}</td><td class='right'>Warning</td></tr>\n";
    print "<tr><td class='left'>${unknown}</td><td class='right'>Unknown</td></tr>\n";
    if ($host_down != -1) {
      print "<tr><td class='left'>${host_down}</td><td class='right'>Host down</td></tr>\n";
    }
    print "</table>\n";
    print "</div>\n";
    if ($url != null) {
      print "</a>\n";
    }
  }

  if (!isset($debug_mode)) {
    print "<div id='time'><span>\n";
    print "<h1>" . date("H:i:s") . "</h1><br>\n";
    print "<h2>Refresh in <span id='counter'>${refresh}</span> seconds</h2>\n";
    print "</span></div>\n";
  }

  foreach(glob("widgets/*.php") as $filename) {
    include $filename;

    if ($query_hosts != "") {
      $result_hosts = fetch($query_hosts);
    } else {
      $result_hosts = "";
    }
    $result_services = fetch($query_services);

    if (isset($debug_mode)) {
      debug($title, $url, $query_hosts, $result_hosts, $query_services, $result_services);
    } else {
      widget($title, $url, $result_hosts, $result_services);
    }
  }

  if (!isset($debug_mode)) {
    foreach($iframe_urls as $url) {
      print "<div class='iframe-container'>\n";
      print "<iframe class='iframe-content' src='${url}' scrolling='no'></iframe>\n";
      print "</div>\n";
    }
  }

  if (isset($debug_mode)) {
    print "<div class='debug'>\n";
    print "<b>Config</b>\n";
    print "<hr>\n";
    print "<pre>\n";
    print "\$livestatus = ${livestatus}\n";
    print "\$refresh = ${refresh}\n";
    if ($iframe_urls != null) {
      $index = 0;
      foreach ($iframe_urls as $url) {
        print "\$iframe_urls[${index}] = <a href='${url}'>${url}</a>\n";
        $index++;
      }
    } else {
        print "\$iframe_urls[] =\n";
    }
    print "</pre>\n";
    print "</div>\n";
  }
?>
</body>
</html>
