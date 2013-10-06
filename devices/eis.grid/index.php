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
print "<b>".eis_widget_text("Grid status: ","gridstatus")."</b><br><br>\n";
// print gauges and labels table
print "<br><br><table style='width:100%'><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><canvas id='power$i' width=270 height=270>[No canvas support]</canvas></td>\n";
print "</tr><tr>\n";
for($i=1;$i<4;$i++) print "<td style='text-align:center'><div id='energy$i'></div></td>\n";
print "</tr></table><br>\n";


//////////// page initialization ////////////
print "<script>\n"; 
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
