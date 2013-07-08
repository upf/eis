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
if (eis_callback("phase")) {
    eis_dev_call($thisdevice,"exec","connect",array("phase"=>$_REQUEST["phase"]),$outputpar);
    die();
}
if (eis_callback("mode")) {
    eis_dev_call($thisdevice,"signal",$_REQUEST["mode"],array(),$outputpar);
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
        <input type='button' value='disable' onClick=\"eis_callback('enable','disable');\" /> ".eis_spaces(6)."\n";
// power on/off buttons
print "<img id='power' align='middle' height=25 width=25> 
        <input type='button' value='power on' onClick=\"eis_callback('power','poweron');\" />
        <input type='button' value='power off' onClick=\"eis_callback('power','poweroff');\" />\n";
// full/half power buttons
print eis_spaces(6)."<img id='powerlevel' align='middle' height=25 width=25> 
        <input type='button' value='full power' onClick=\"eis_callback('mode','fullpower');\" />
        <input type='button' value='half power' onClick=\"eis_callback('mode','halfpower');\" />\n";

// cline and gline status
if (array_key_exists("cline",$eis_dev_status)) print eis_spaces(6)."<b><i>cline: ".$eis_dev_status["cline"]."</i></b>\n";
if (array_key_exists("gline",$eis_dev_status)) print eis_spaces(6)."<b><i>gline: ".$eis_dev_status["gline"]."</i></b>\n";
// print gauges and labels table for cline (only for loads) and select radio buttons
print "<br><br><table style='width:100%'><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><canvas id='cgauge$i' width=230 height=230>[No canvas support]</canvas></td>\n";
print "</tr><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><div id='cenergy$i'></div></td>\n";
print "</tr><tr>\n";
for($i=1;$i<4;$i++)
    print "<td style='text-align:center'><input type='radio' id='phase$i' onclick=\"eis_callback('phase','$i');\"> connect to phase $i</td>\n";
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
// set powerlevel field
if ($eis_dev_status["powerlevel"]==1) $powerlevel="full-on.jpg"; else $powerlevel="full-off.jpg"; 
print "document.getElementById('powerlevel').src='$powerlevel';\n";
// set gauges parameters and draw gauges and labels only for loads
for($i=1;$i<4;$i++) {
    $oldstatus=$eis_dev_status;
    if (array_key_exists("cpower$i",$eis_dev_conf)) $cpower_conf=$eis_dev_conf["cpower$i"]; else $cpower_conf=0;
    if (array_key_exists("cpower$i",$oldstatus)) $cpower_stat=$oldstatus["cpower$i"]; else $cpower_stat=0;
    if (array_key_exists("cenergy$i",$oldstatus)) $cenergy=$oldstatus["cenergy$i"]; else $cenergy=0;
    $powermin=0;
    $powermax=$cpower_conf;
    $powerval=$cpower_stat;
    $powerred=$cpower_conf*0.7;
    $powergreen=$cpower_conf*0.3;
    // init Javascript power and energy variables
    print "var cpower$i=$cpower_stat;
    var cenergy$i=$cenergy;\n";
    // draw gauge
    print "var cgauge$i = new RGraph.Gauge('cgauge$i', $powermin, $powermax, $powerval);
        cgauge$i.Set('chart.title','phase $i');
        cgauge$i.Set('chart.red.start','$powerred');
        cgauge$i.Set('chart.green.end','$powergreen');
        cgauge$i.Set('chart.title.bottom', 'watt');
        cgauge$i.Set('chart.green.color','green');
        cgauge$i.Set('chart.red.color','red');
        RGraph.Effects.Gauge.Grow(cgauge$i);
    ";
    // draw labels
    print "var ev=$cenergy;
    document.getElementById('cenergy$i').innerHTML = '<h3><font color=red>'+ev.toFixed(3)+' kWh</font></h3>';\n";
}
// set phase connection
print "document.getElementById('phase".$eis_dev_status["connected"]."').checked=true;\n";
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
                case "powerlevel":
                    if (status[i]==1) s="full-on.jpg"; else s="full-off.jpg"; 
                    document.getElementById('powerlevel').src = s;
                    break;
                case "connected":
                    for(p=1;p<4;p++)
                        if(p==status[i])
                            document.getElementById('phase'+p).checked=true;
                        else
                            document.getElementById('phase'+p).checked=false;
                    break;
                // load cases
                case "cpower1": cpower1=status[i]; break;
                case "cpower2": cpower2=status[i]; break;
                case "cpower3": cpower3=status[i]; break;
                case "cenergy1": cenergy1=status[i]; break;
                case "cenergy2": cenergy2=status[i]; break;
                case "cenergy3": cenergy3=status[i]; break;
           }
        // load cases updating
        cgauge1.value=cpower1;
        RGraph.Effects.Gauge.Grow(cgauge1);                    
        cgauge2.value=cpower2;
        RGraph.Effects.Gauge.Grow(cgauge2);                    
        cgauge3.value=cpower3;
        RGraph.Effects.Gauge.Grow(cgauge3);
        document.getElementById('cenergy1').innerHTML = '<h3><font color=red>'+cenergy1.toFixed(3)+' kWh</font></h3>';
        document.getElementById('cenergy2').innerHTML = '<h3><font color=red>'+cenergy2.toFixed(3)+' kWh</font></h3>';
        document.getElementById('cenergy3').innerHTML = '<h3><font color=red>'+cenergy3.toFixed(3)+' kWh</font></h3>';
     }

</script>


</body>
</html>
