<?php

// **** EIS ****
// eis device realtime interface
// upf, May2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");

// realtime configuration
$port=$eis_dev_conf["ifport"];

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

// print page headers
$headers='
    <script src="../lib/RGraph/libraries/RGraph.common.core.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.common.dynamic.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.gauge.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.led.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.thermometer.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.vprogress.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.odo.js" ></script>
    <!--[if lt IE 9]><script src="../excanvas/excanvas.js"></script><![endif]-->
    <script src="../lib/RGraph/libraries/RGraph.common.effects.js" ></script>
    <script src="../lib/jquery.min.js" ></script>
    <style>
        td {text-align: center}
    </style>';
print eis_page_header($eis_dev_conf["ID"],$headers,"picture.jpg");

// timestamp field
print "<h3><div id='timestamp'></div></h3>\n";     

// enable/disable buttons
if ($eis_dev_status["enabled"]) $enabled="../lib/red-on.png"; else $enabled="../lib/red-off.png"; 
print "<img id='enabled' align='middle' height=25 width=25 src='$enabled'>
        <input type='button' value='enable' onClick=\"eis_callback('enable','enable');\" />
        <input type='button' value='disable' onClick=\"eis_callback('enable','disable');\" /> &nbsp &nbsp &nbsp &nbsp\n";

// power on/off buttons
if ($eis_dev_status["power"]) $power="../lib/green-on.png"; else $power="../lib/green-off.png"; 
print "&nbsp &nbsp &nbsp &nbsp <img id='power' align='middle' height=25 width=25 src='$power'> 
        <input type='button' value='power on' onClick=\"eis_callback('power','poweron');\" />
        <input type='button' value='power off' onClick=\"eis_callback('power','poweroff');\" />\n";

// canvas
print "<br><br><table><tr><td><canvas id='temperature' width=80 height=350>[No canvas support]</canvas>&nbsp &nbsp </td>\n";
print "<td><canvas id='humidity' width=80 height=350>[No canvas support]</canvas>&nbsp &nbsp </td>\n";
print "<td><canvas id='radiation' width=80 height=350>[No canvas support]</canvas>&nbsp &nbsp </td> \n";
print "<td><canvas id='barometer' width=250 height=250>[No canvas support]</canvas>&nbsp &nbsp </td>\n";
print "<td><canvas id='windspeed' width=250 height=250>[No canvas support]</canvas>&nbsp &nbsp </td>\n";
print "<td><canvas id='winddir' width=250 height=250>[No canvas support]</canvas>&nbsp &nbsp </td></tr></table>\n";

?> 
    
<script>
 
    //////// fill page for the first time

    // simulation time
    document.getElementById('timestamp').innerHTML = 'simulation time: '+eis_date(<?php print $eis_dev_status["timestamp"];?>);

    // temperature widget
    var thermometer = new RGraph.Thermometer('temperature', -10,50,<?php print $eis_dev_status["temperature"];?>);
    var grad = thermometer.context.createLinearGradient(15,0,85,0);
    grad.addColorStop(0,'green');
    grad.addColorStop(0.5,'#9f9');
    grad.addColorStop(1,'green');
    thermometer.Set('chart.colors', [grad]);
    thermometer.Set('chart.title.side', 'Temperature C');
    thermometer.Set('chart.scale.visible', true);
    thermometer.Set('chart.scale.decimals', 1);
    thermometer.Set('chart.gutter.right', 25);
    RGraph.Effects.Thermometer.Grow(thermometer);

    // humidity
    var humidity = new RGraph.VProgress('humidity', <?php print $eis_dev_status["humidity"];?>,100);            
    humidity.Set('colors', [RGraph.LinearGradient(humidity, 0,25,0,425,'white', 'blue')]);
    humidity.Set('gutter.right', 45);
    humidity.Set('chart.title.side', 'Humidity %');
    humidity.Draw();

    // radiation
    var radiation = new RGraph.VProgress('radiation', <?php print $eis_dev_status["radiation"];?>,1100);            
    radiation.Set('colors', [RGraph.LinearGradient(radiation, 0,25,0,425,'white', 'red')]);
    radiation.Set('gutter.right', 45);
    radiation.Set('chart.title.side', 'radiation w/m2');
    radiation.Draw();

    // pressure
    var pressure = new RGraph.Gauge('barometer', 900, 1100, <?php print $eis_dev_status["pressure"];?>);
    pressure.Set('chart.tickmarks.small', 50);
    pressure.Set('chart.tickmarks.big',5);
    pressure.Set('chart.title.top', 'Pressure');
    pressure.Set('chart.title.top.size', 18);
    pressure.Set('chart.title.bottom', 'mBar');
    pressure.Set('chart.title.bottom.color', '#aaa');
    pressure.Set('chart.colors.ranges', [[900, 930, 'red'], [930, 960, 'yellow'], [1040, 1070, 'yellow'], [1070, 1100, 'red']]);
    RGraph.Effects.Gauge.Grow(pressure);

   // wind speed
    var wind = new RGraph.Gauge('windspeed', 0, 100, <?php print $eis_dev_status["windspeed"]*3.6;?>);
    wind.Set('chart.tickmarks.small', 50);
    wind.Set('chart.tickmarks.big',5);
    wind.Set('chart.title.top', 'Wind Speed');
    wind.Set('chart.title.top.size', 17);
    wind.Set('chart.title.bottom', 'Kmh');
    wind.Set('chart.title.bottom.color', '#aaa');
    wind.Set('chart.colors.ranges', [[80, 100, 'red'], [60, 80, 'yellow']]);
    RGraph.Effects.Gauge.Grow(wind);

    // wind dir
    var odo1 = new RGraph.Odometer('winddir', 0, 360, <?php print $eis_dev_status["winddir"];?>);
    odo1.Set('chart.needle.color', 'black');
    odo1.Set('chart.needle.tail', false);
    odo1.Set('chart.label.area', 35);
    odo1.Set('chart.border', RGraph.isOld() ? false : true);
    odo1.Set('chart.labels', ['N','NE','E','SE','S','SW','W','NW']);
    odo1.Set('chart.value.text', true);
    odo1.Set('chart.value.units.post', '°');
    odo1.Set('chart.tickmarks', false);
    odo1.Set('chart.green.color', 'Gradient(white:green)');
    odo1.Set('chart.yellow.color', 'Gradient(white:yellow)');
    odo1.Set('chart.red.color', 'Gradient(white:red)');   
    if (navigator.userAgent.indexOf('Firefox') > 0) {
        odo1.Set('chart.tickmarks', false);
        odo1.Set('chart.tickmarks.highlighted', true);
    }
    odo1.Draw();


</script>


<script>
//////////// page realtime update ////////////

    // update page using new device status values
    // required by eis_realtime_handler();
    function eis_updatepage(status) {
        var i,s,c;
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
                 case "temperature":
                    thermometer.value=status[i];
                    RGraph.Effects.Thermometer.Grow(thermometer);                    
                    break;
                 case "humidity":
                    humidity.value=status[i];
                    humidity.Draw();
                    break;
                 case "pressure":
                    pressure.value=status[i];
                    RGraph.Effects.Gauge.Grow(pressure);
                    break;
                 case "windspeed":
                    wind.value=status[i]*3.6;
                    RGraph.Effects.Gauge.Grow(wind);
                    break;
                 case "winddir":
                    odo1.value=status[i];
                    odo1.Draw();
                    break;
                 case "radiation":
                    radiation.value=status[i];
                    radiation.Draw();
                    break;
           }
    }

</script>


</body>
</html>
