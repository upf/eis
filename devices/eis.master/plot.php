<?php

// **** EIS ****
// eis realtime plotting service
// upf, May-Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");

// initialization
$thispage="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
$thisdevice=$eis_dev_conf["ID"]."@".$_SERVER["SERVER_NAME"];


// create a realtime interface handler
// requires the Javascript function "eis_updatepage(status)" be defined into the page
eis_realtime_handler();


//////////// page creation ////////////

// set page headers, put here any additional needed headers (e.g. RGraph includes) 
$headers='
    <script src="../lib/RGraph/libraries/RGraph.common.core.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.common.dynamic.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.gauge.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.line.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.thermometer.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.vprogress.js" ></script>
    <!--[if lt IE 9]><script src="../excanvas/excanvas.js"></script><![endif]-->
    <script src="../lib/RGraph/libraries/RGraph.common.effects.js"></script>
    <script src="../lib/jquery.min.js"></script>
    ';
// output standard eis page 
print eis_page_header($eis_dev_conf["ID"],$headers,"");

// plot
print "<canvas id='plot' width=800 height=550>[No canvas support]</canvas>\n";

?>

<script>
//////////// page initialization ////////////

    var data = 
    [
        [46.94,30.76,14.85,4.79,2.07,0.58],
        [46,30.68,15.68,5.09,2,0.55],
        [45.44,30.37,16.54,5.08,2,0.55],
        [45.11,29.98,17.37,5.02,1.97,0.54],
        [44.52,29.67,18.29,5.04,1.91,0.57],
        [43.87,29.29,19.36,5.01,1.84,0.63],
        [43.58,28.34,20.65,5.07,1.74,0.61],
        [42.45,27.95,22.14,5.17,1.66,0.63],
        [41.89,27.49,23.16,5.19,1.67,0.61],
        [41.66,26.79,23.61,5.6,1.72,0.62],
        [40.18,26.39,25,5.93,1.81,0.69],
        [40.63,25.23,25.69,5.92,1.82,0.71],
        [38.65,25.27,27.27,6.08,1.98,0.75]
    ]
    
    var data2 = [];
    var line = [];
    
    for (var b=0; b<6; ++b) {
        for (var i=0; i<data.length; ++i) {
            line.push(data[i][b]);
        }
        
        data2[b] = RGraph.array_clone(line);
        line = [];
    }

    var line5 = new RGraph.Line('plot', data2);
    line5.Set('chart.labels', ['Dec 2010','\r\nJan 2011','Feb 2011','\r\nMar 2011','Apr 2011','\r\nMay 2011','Jun 2011','\r\nJul 2011','Aug 2011','\r\nSep 2011','Oct 2011','\r\nNov 2011','Dec 2011']);
    line5.Set('chart.key', ["IE","Firefox","Chrome","Safari","Opera","Other"]);
    line5.Set('chart.tickmarks', null);
    line5.Set('chart.shadow', true);
    line5.Set('chart.shadow.offsetx', 1);
    line5.Set('chart.shadow.offsety', 1);
    line5.Set('chart.shadow.blur', 3);
    line5.Set('chart.hmargin', 15);
    line5.Set('chart.gutter.top', 45);
    line5.Set('chart.gutter.bottom', 45);
    line5.Set('chart.background.grid.vlines', false);
    line5.Set('chart.title', 'Browser share (Jan 2012)');
    line5.Set('chart.title.vpos', 0.5);
    line5.Set('chart.background.grid.border', false);
    line5.Draw();




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
