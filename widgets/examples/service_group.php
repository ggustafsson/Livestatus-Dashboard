<?php
  $title = ""; // Title of widget.
  $url = ""; // URL on widget div.

  // Filter rules for hosts in widget. This can be left empty if it does not
  // apply to your situation, for example for dummy hosts.
  // Livestatus info: https://mathias-kettner.de/checkmk_livestatus.html
  $query_hosts = "";

  // Filter rules for services in widget.
  $query_services = <<<"EOQ"
GET services
Filter: host_name = deep_pings_ageo_prod
Filter: active_checks_enabled = 1
Filter: host_scheduled_downtime_depth = 0
Filter: scheduled_downtime_depth = 0
Filter: state_type = 1
Stats: state = 0
Stats: state = 1
Stats: state = 2
Stats: state = 3
OutputFormat: json
EOQ;
?>
