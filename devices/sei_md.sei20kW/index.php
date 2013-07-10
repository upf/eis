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

// process callbacks from page
if (eis_callback("enable")) {
    eis_dev_call($thisdevice,"signal",$_REQUEST["enable"],array(),$outputpar);
    die();
}
if (eis_callback("power")) {
    eis_dev_call($thisdevice,"signal",$_REQUEST["power"],array(),$outputpar);
    die();
}

// create a realtime interface handler
// requires the Javascript function "eis_updatepage(status)" be defined into the page
eis_realtime_handler();


//////////// page creation ////////////

// set page headers, put here any additional needed headers (e.g. RGraph includes) 
$headers='
    <script src="../lib/RGraph/libraries/RGraph.common.core.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.common.dynamic.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.gauge.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.led.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.thermometer.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.vprogress.js" ></script>
    <!--[if lt IE 9]><script src="../excanvas/excanvas.js"></script><![endif]-->
    <script src="../lib/RGraph/libraries/RGraph.common.effects.js"></script>
    <script src="../lib/jquery.min.js"></script>
    ';
// output standard eis page 
print eis_page_header($eis_dev_conf["ID"],$headers,"picture.jpg");
// timestamp field
print "<h3><div id='timestamp'></div></h3>\n";     
// enable/disable buttons
print "<img id='enabled' align='middle' height=25 width=25>
        <input type='button' value='enable' onClick=\"eis_callback('enable','enable');\" />
        <input type='button' value='disable' onClick=\"eis_callback('enable','disable');\" /> ".eis_spaces(8)."\n";
// power on/off buttons
print "<img id='power' align='middle' height=25 width=25> 
        <input type='button' value='power on' onClick=\"eis_callback('power','poweron');\" />
        <input type='button' value='power off' onClick=\"eis_callback('power','poweroff');\" />\n";
// cline and gline status
if (array_key_exists("cline",$eis_dev_status)) print eis_spaces(4)."<b><i>cline: ".$eis_dev_status["cline"]."</i></b>\n";
if (array_key_exists("gline",$eis_dev_status)) print eis_spaces(4)."<b><i>gline: ".$eis_dev_status["gline"]."</i></b>\n";
// print gauges and labels table for gline (only for generators)
print "<br><table style='width:100%'><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><canvas id='ggauge$i' width=230 height=230>[No canvas support]</canvas></td>\n";
print "</tr><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><div id='genergy$i'></div></td>\n";
print "</tr></table><br>\n";


//////////// page initialization ////////////
print "<script>\n"; 
// set timestamp field
print "document.getElementById('timestamp').innerHTML = 'simulation time: '+eis_date(".$eis_dev_status["timestamp"].");\n";
// set enable field
if ($eis_dev_status["enabled"]) $enabled="../lib/red-on.png"; else $enabled="../lib/red-off.png"; 
print "document.getElementById('enabled').src='$enabled';\n";
// set power field
if ($eis_dev_status["power"]) $power="../lib/green-on.png"; else $power="../lib/green-off.png"; 
print "document.getElementById('power').src='$power';\n";
// set gauges parameters and draw gauges and labels only for generators
for($i=1;$i<4;$i++) {
    $oldstatus=$eis_dev_status;
    if (array_key_exists("gpower$i",$eis_dev_conf)) $gpower_conf=$eis_dev_conf["gpower$i"]; else $gpower_conf=0;
    if (array_key_exists("gpower$i",$oldstatus)) $gpower_stat=$oldstatus["gpower$i"]; else $gpower_stat=0;
    if (array_key_exists("genergy$i",$oldstatus)) $genergy=$oldstatus["genergy$i"]; else $genergy=0;
    $powermin=0;
    $powermax=$gpower_conf;
    $powerval=$gpower_stat;
    $powerred=$gpower_conf*0.7;
    $powergreen=$gpower_conf*0.3;
    // init Javascript power and energy variables
    print "var gpower$i=$gpower_stat;
    var genergy$i=$genergy;\n";
    // draw gauge
    print "var ggauge$i = new RGraph.Gauge('ggauge$i', $powermin, $powermax, $powerval);
        ggauge$i.Set('chart.title','phase $i');
        ggauge$i.Set('chart.red.start','$powerred');
        ggauge$i.Set('chart.green.end','$powergreen');
        ggauge$i.Set('chart.title.bottom', 'watt');
        ggauge$i.Set('chart.green.color','red');
        ggauge$i.Set('chart.red.color','green');
        RGraph.Effects.Gauge.Grow(ggauge$i);
    ";
    // draw labels
    print "var ev=$genergy;
    document.getElementById('genergy$i').innerHTML = '<h3><font color=green>'+ev.toFixed(3)+' kWh</font></h3>';";
}
print "</script>\n";

?> 


<script>
//////////// page realtime update ////////////

    // update page using new device status values
    // required by eis_realtime_handler();
    function eis_updatepage(status) {
        var i,s,c;
        var e=0.0;
        for (i in status)
            switch(i) {
                case "timestamp":
                    document.getElementById('timestamp').innerHTML = "simulation time: "+eis_date(status[i]);
                    break;
                case "enabled":
                    if (status[i]) s="../lib/red-on.png"; else s="../lib/red-off.png"; 
                    document.getElementById('enabled').src = s;
                    break;
                case "power":
                    if (status[i]) s="../lib/green-on.png"; else s="../lib/green-off.png"; 
                    document.getElementById('power').src = s;
                    break;
                 // generator cases
                case "gpower1": gpower1=status[i]; break;
                case "gpower2": gpower2=status[i]; break;
                case "gpower3": gpower3=status[i]; break;
                case "genergy1": genergy1=status[i]; break;
                case "genergy2": genergy2=status[i]; break;
                case "genergy3": genergy3=status[i]; break;
           }
       // generator cases updating
        ggauge1.value=gpower1;
        RGraph.Effects.Gauge.Grow(ggauge1);                    
        ggauge2.value=gpower2;
        RGraph.Effects.Gauge.Grow(ggauge2);                    
        ggauge3.value=gpower3;
        RGraph.Effects.Gauge.Grow(ggauge3);
        document.getElementById('genergy1').innerHTML = '<h3><font color=green>'+genergy1.toFixed(3)+' kWh</font></h3>';
        document.getElementById('genergy2').innerHTML = '<h3><font color=green>'+genergy2.toFixed(3)+' kWh</font></h3>';
        document.getElementById('genergy3').innerHTML = '<h3><font color=green>'+genergy3.toFixed(3)+' kWh</font></h3>';
     }

</script>


</body>
</html>
