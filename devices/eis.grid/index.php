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
print eis_page_header($eis_dev_conf["ID"],$headers);
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
// print gauges and labels table
print "<br><br><table style='width:100%'><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><canvas id='power$i' width=270 height=270>[No canvas support]</canvas></td>\n";
print "</tr><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><div id='energy$i'></div></td>\n";
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
// set gauges parameters and draw gauges and labels
for($i=1;$i<4;$i++) {
    $oldstatus=$eis_dev_status;
    if (array_key_exists("cpower$i",$eis_dev_conf)) $cpower_conf=$eis_dev_conf["cpower$i"]; else $cpower_conf=0;
    if (array_key_exists("gpower$i",$eis_dev_conf)) $gpower_conf=$eis_dev_conf["gpower$i"]; else $gpower_conf=0;
    if (array_key_exists("cpower$i",$oldstatus)) $cpower_stat=$oldstatus["cpower$i"]; else $cpower_stat=0;
    if (array_key_exists("gpower$i",$oldstatus)) $gpower_stat=$oldstatus["gpower$i"]; else $gpower_stat=0;
    if (array_key_exists("cenergy$i",$oldstatus)) $cenergy=$oldstatus["cenergy$i"]; else $cenergy=0;
    if (array_key_exists("genergy$i",$oldstatus)) $genergy=$oldstatus["genergy$i"]; else $genergy=0;
    $powermin=-$cpower_conf;
    $powermax=$gpower_conf;
    $powerval=$gpower_stat-$cpower_stat;
    $powergreen=-$cpower_conf*0.25;
    $powerred=$gpower_conf*0.25;
    // init Javascript power and energy variables
    print "var cpower$i=$cpower_stat;
    var gpower$i=$gpower_stat;
    var cenergy$i=$cenergy;
    var genergy$i=$genergy;\n";
    // draw gauge in kW
    print "var power$i = new RGraph.Gauge('power$i', $powermin, $powermax, ".($powerval).");
        power$i.Set('chart.title','phase $i');
        power$i.Set('chart.red.start','$powerred');
        power$i.Set('chart.green.end','$powergreen');
        power$i.Set('chart.title.bottom', 'W');
        power$i.Set('chart.green.color','green');
        power$i.Set('chart.red.color','red');
        RGraph.Effects.Gauge.Grow(power$i);
    ";
    // draw labels
    print "var ev=$genergy-$cenergy;
            if (ev>0) cv='red'; else cv='green';
            document.getElementById('energy$i').innerHTML = '<h3><font color='+cv+'>'+ev.toFixed(3)+' kWh</font></h3>';
    ";
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
                case "cpower1": cpower1=status[i]; break;
                case "cpower2": cpower2=status[i]; break;
                case "cpower3": cpower3=status[i]; break;
                case "gpower1": gpower1=status[i]; break;
                case "gpower2": gpower2=status[i]; break;
                case "gpower3": gpower3=status[i]; break;
                case "cenergy1": cenergy1=status[i]; break;
                case "cenergy2": cenergy2=status[i]; break;
                case "cenergy3": cenergy3=status[i]; break;
                case "genergy1": genergy1=status[i]; break;
                case "genergy2": genergy2=status[i]; break;
                case "genergy3": genergy3=status[i]; break;
           }
        power1.value=(gpower1-cpower1);
        RGraph.Effects.Gauge.Grow(power1);                    
        power2.value=(gpower2-cpower2);
        RGraph.Effects.Gauge.Grow(power2);                    
        power3.value=(gpower3-cpower3);
        RGraph.Effects.Gauge.Grow(power3);
        e=genergy1-cenergy1;
        if (e>0) c='red'; else c='green';
        document.getElementById('energy1').innerHTML = '<h3><font color='+c+'>'+e.toFixed(3)+' kWh</font></h3>';
        e=genergy2-cenergy2;
        if (e>0) c='red'; else c='green';
        document.getElementById('energy2').innerHTML = '<h3><font color='+c+'>'+e.toFixed(3)+' kWh</font></h3>';
        e=genergy3-cenergy3;
        if (e>0) c='red'; else c='green';
        document.getElementById('energy3').innerHTML = '<h3><font color='+c+'>'+e.toFixed(3)+' kWh</font></h3>';
    }

</script>


</body>
</html>
