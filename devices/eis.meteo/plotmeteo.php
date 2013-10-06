<?php

// **** EIS ****
// meteo data plotter
// upf, MJul2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");
include("private/device.php");

// realtime configuration
$port=$eis_dev_conf["ifport"];

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
$headers='
    <script src="../lib/RGraph/libraries/RGraph.thermometer.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.odo.js" ></script>';
print eis_page_header($eis_dev_conf["ID"],$headers);
print "<br><h3 style='text-align:center'>".eis_widget_text("Plots of data set: ","sim_meteo").
    " (starting ".date("l d-M-Y",$eis_dev_status['sim_startime'])." at 00:00)</h3><br>\n";

// meteo data vars, min and max
$meteodata=array("temperature"=>array(-10,40),"humidity"=>array(0,100),"windspeed"=>array(0,30),
    "winddir"=>array(0,360),"pressure"=>array(950,1100),"radiation"=>array(0,1200));
// compute x labels
$start=$eis_dev_conf["dataIDs"][$eis_dev_status["sim_meteo"]]['start'];
$end=$start+24*3600;

//die($start."  ".$end."  ".($end-1800));

$hours=4;
$nlabels=intval(($end-$start-3600*$hours)/(3600*$hours));
$h=date("G",$start)+$hours/2;
$labels="['$h'";
for($i=1;$i<=$nlabels;$i++) $labels=$labels.",'".($h+$i*$hours)."'";
$labels=$labels."]";
foreach($meteodata as $k=>$d) {
    // output canvas
    print "<canvas id='$k' width=700 height=400>[No canvas support]</canvas><br>\n";
    // reset oldata
    if ($eis_dev_status["sim_meteo"]!="random") {
    $query="SELECT * FROM ".$eis_dev_conf["tablepfx"]."_meteodata WHERE timestamp=$start";
    if (!($result=$eis_mysqli->query($query))) die($eis_dev_conf["ID"].":cannotLoadMeteodata  ".$eis_mysqli->error);
    //if (($result->num_rows!=1)) die($eis_dev_conf["ID"].":wrongStoredMeteodata ".print_r($row,true));
    $eis_dev_status["oldata"]=$result->fetch_array(MYSQLI_ASSOC);
    }
    // create dataset (10 min timestep)
    $data="[";
    for ($t=$start;$t<=$end;$t+=600) {
        compute_meteo($t);
        $v=$eis_dev_status[$k];
        $data=$data."[$t,$v],";
    }
    $data=$data."]";
    // output javascript plot
    print "<script>
    var plot_$k = new RGraph.Scatter('$k',$data);
        plot_$k.Set('chart.title','$k');
        plot_$k.Set('chart.xmin',$start);
        plot_$k.Set('chart.xmax',$end);
        plot_$k.Set('chart.title.xaxis','hours');
        plot_$k.Set('chart.labels',$labels);
        plot_$k.Set('chart.ymin',".$d[0].");
        plot_$k.Set('chart.ymax',".$d[1].");
        plot_$k.Set('chart.gutter.left',100);
        plot_$k.Set('chart.gutter.bottom',100);
        plot_$k.Set('chart.tickmarks',null);
        plot_$k.Set('chart.line',true);
        plot_$k.Set('chart.line.colors',['red']);
        plot_$k.Set('chart.line.linewidth',2);
        plot_$k.Draw();
    </script>\n";
}

?> 


</body>
</html>
