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

eis_realtime_widgets();

print eis_page_header($eis_dev_conf["ID"],"");

// output led, buttons and setbox
print "<h3>".eis_widget_timestamp("Simulation time: ")."</h3>";
print eis_widget_enable().eis_spaces(6).eis_widget_poweron().eis_spaces(6);
print eis_widget_led("chargebattery","orange").eis_widget_signal("charger on","chargebatteryon").
        eis_widget_signal("charger off","chargebatteryoff").eis_spaces(6);
print eis_widget_setvar("bypass",0,100);

//ouput gragical widgets
$hoptions=array("height"=>300);
$options=array("width"=>600, "height"=>350);
print "<br><br><table style='width:100%'>
	<tr><td>".eis_widget_blackout()."</td><td>".eis_widget_vbar("bypass %","bypass",100,array("height"=>200)).
	"<img src='ups.tiff' width=500 height=200></td><td><b>".eis_widget_text("protected gline: ","glinestatus")."</b></td></tr>
	<tr><td>";
print "<table><tr>\n";
for($p=1;$p<4;$p++) print "<td>".eis_widget_vbar("phase$p","cpower$p",$eis_dev_conf["cpower$p"],$hoptions)."</td>";
print "</tr>\n<tr>";
for($p=1;$p<4;$p++) print "<td><b>".eis_spaces(2).eis_widget_text("kWh ","cenergy$p",array("float"=>2))."</b></td>";
print "</tr></table>\n";
print "</td><td><br><br>".eis_widget_plot("Battery Charge %","benergy",0,100,$options)."</td><td>\n";
print "<table><tr>\n";
for($p=1;$p<4;$p++) print "<td>".eis_widget_vbar("phase$p","gpower$p",$eis_dev_conf["gpower$p"],$hoptions)."</td>";
print "</tr>\n<tr>";
for($p=1;$p<4;$p++) print "<td><b>".eis_spaces(2).eis_widget_text("kWh ","genergy$p",array("float"=>2))."</b></td>";
print "</tr></table>\n";
print "</td></tr></table>\n";


?> 


</body>
</html>
