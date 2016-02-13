#!/usr/bin/python

import os.path
import socket

filter = [ "" ]
filter_ends = ("_preprod", "_qa", "_val")
widget_path = "/opt/monitor/livestatus-dashboard/widgets"

template = """<?php
  $title = "REPLACE_TITLE";
  $url = "https://10.57.78.30/monitor/index.php/monitor/index.php/listview?q=%5Bservices%5D%20host.name%3D%22REPLACE_URL%22";

  // Livestatus info: https://mathias-kettner.de/checkmk_livestatus.html
  $query_hosts = "";

  $query_services = <<<"EOQ"
GET services
Filter: host_name = REPLACE_NAME
Filter: active_checks_enabled = 1
Filter: notifications_enabled = 1
Filter: host_scheduled_downtime_depth = 0
Filter: scheduled_downtime_depth = 0
Filter: acknowledged = 0
Filter: state_type = 1
Stats: state = 0
Stats: state = 1
Stats: state = 2
Stats: state = 3
OutputFormat: json
EOQ;
?>"""

socket_path = "/opt/monitor/var/rw/live"
s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
s.connect(socket_path)
s.send("GET hosts\nColumns: name\nFilter: name ~~ deep_pings_\n")
s.shutdown(socket.SHUT_WR)

result = s.recv(100000000).split("\n")

for host in result:
    if host != "" and host not in filter and not host.endswith(filter_ends):
        file = "%s/%s.php" % (widget_path, host)
        if os.path.exists(file):
            continue
        title = host.replace("deep_pings_", "")
        widget = template
        widget = widget.replace("REPLACE_TITLE", title)
        widget = widget.replace("REPLACE_URL", host)
        widget = widget.replace("REPLACE_NAME", host)
        print "Creating file %s" % file
        f = open(file, "w")
        f.write(widget + "\n")
        f.close()
