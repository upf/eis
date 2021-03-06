<?php

// **** EIS ****
// eis device realtime interface
// upf, May-Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");

// initialization
$thispage="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
$thisdevice=$eis_dev_conf["ID"]."@".$_SERVER["SERVER_NAME"];

// create a realtime interface handler
// if custom widgets are defined, requires the Javascript function "eis_updatepage(status)" be defined into the page
eis_realtime_handler();

// use widgets
eis_realtime_widgets();


//////////// page creation ////////////

// output page
print eis_page_header($eis_dev_conf["ID"],"");
// output some useful widgets
print "<h3>".eis_widget_timestamp("Simulation time: ")."</h3>";
print eis_widget_enable().eis_spaces(6).eis_widget_poweron().eis_spaces(6);
print "<b>".eis_widget_text("Generator status: ","glinestatus")."</b><br><br>\n";
// power gauges
print eis_widget_gpower("")."<br>\n";
// plots
for($p=1;$p<4;$p++)
	if ($eis_dev_conf["gpower$p"]) print eis_widget_plot("Generated power on phase $p (W)","gpower$p",0,$eis_dev_conf["gpower$p"]);

?>

</body>
</html>
