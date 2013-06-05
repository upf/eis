<?php

// **** EIS ****
// eis device realtime interface
// upf, May2013

// required includes
require_once("/etc/eis.conf");
include("device_conf.php");
include($eis["path"]."/system/eis_device_lib.php");

// realtime configuration
$port=$eis_device["ifport"];

// initialization
$thispage="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
$controlpage="http://".$_SERVER["SERVER_NAME"].str_replace(end(explode('/',$_SERVER["SCRIPT_NAME"])),'',$_SERVER["SCRIPT_NAME"])."control.php";
date_default_timezone_set($eis["timezone"]);
$start=time();
if (!eis_load_status()) die ($eis["error"]." --> ".$eis["errmsg"]);
$oldstatus=$eis_device["status"];

// process calls from page
if (isset($_REQUEST["callback"])) {
    if (isset($_REQUEST["enable"]))
        if ($_REQUEST["enable"])
            eis_call($controlpage,time(),"interface","signal","enable",array(),$returnmsg);
        else
            eis_call($controlpage,time(),"interface","signal","disable",array(),$returnmsg);
    if (isset($_REQUEST["power"]))
        if ($_REQUEST["power"])
            eis_call($controlpage,time(),"interface","signal","poweron",array(),$returnmsg);
        else
            eis_call($controlpage,time(),"interface","signal","poweroff",array(),$returnmsg);
    die();
}

// listen for reload on a socket server and send changed status values as Json array
if (isset($_REQUEST["realtime"])) {
    // init UDP server
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
    socket_bind($socket,'127.0.0.1',$port);
    if (!$socket) die("Fatal error: ". socket_last_error());
    // wait for status reload signal
    while (true) {
        socket_recvfrom($socket, $d, 6, 0, $a,$p);
        // send realtime data if any
        if ($d=="reload") {
            if (!eis_load_status()) die ($eis["error"]." --> ".$eis["errmsg"]);
            $changed=array();
            foreach($eis_device["status"] as $key=>$value) if ($value!=$oldstatus[$key]) $changed[$key]=$value;
            print json_encode($changed);
            break;
        }
    }
    socket_close($socket);
    die();
}

// HTML initial page follows
?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php print $eis_device["ID"];?></title>
    <script src="../lib/RGraph/libraries/RGraph.common.core.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.common.dynamic.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.gauge.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.led.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.thermometer.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.vprogress.js" ></script>
    <!--[if lt IE 9]><script src="../excanvas/excanvas.js"></script><![endif]-->
    <script src="../lib/RGraph/libraries/RGraph.common.effects.js" ></script>
    <script src="../lib/jquery.min.js" ></script>
    <style>
        td {text-align: center}
    </style>
 </head>
    <body>

    <h1><?php print $eis_device["ID"];?></h1>
    <h2><div id='s_time'></div></h2>
    <h2><div id='status'></div></h2>
 
    <table style="width:70%">
    <tr>
        <td><canvas id="power" width="200" height="200">[No canvas support]</canvas></td>
        <td><div id='switch'></div></td>
        <td>
            <form><input type="button" value="on" onClick="sendback('power','1')" /></form>
            <br>
            <form><input type="button" value="off" onClick="sendback('power','0')" /></form>
        </td>
        <td>
            <form><input type="button" value="enable" onClick="sendback('enable','1')" /></form>
            <br>
            <form><input type="button" value="disable" onClick="sendback('enable','0')" /></form>
        </td>
    </tr>
    <tr>
        <td>Current Load Power</td>
        <td>on/off Status</td>
        <td>power on/off</td>
        <td>enable/disable</td>
    </tr>
    </table>
    
    <br>
    
<script>
    // return a formatted data string froma UNIX timestamp
    function getfdate(timestamp) {
        var d = new Date(timestamp*1000);
        return d.getDate()+"-"+(d.getMonth()+1)+"-"+d.getFullYear()+"  "+d.getHours()+":"+d.getMinutes()+":"+d.getSeconds();
    }

    // fill page for the first time

    // enable/disable status
    document.getElementById('status').innerHTML = '(<?php if ($eis_device["status"]["enabled"]) print "enabled"; else print "<font color=red>disabled</font>";?>)';

    // simulation time
    document.getElementById('s_time').innerHTML = getfdate(<?php print $eis_device["status"]["timestamp"];?>);

    // on/off status
    document.getElementById('switch').innerHTML = '<img width="80" height="30" src=<?php if ($oldstatus["power"]) $img="on.png"; else $img="off.png";print $img;?> >';

    // current power gauge
    var p1=<?php print $oldstatus["cpower1"];?>;
    var gauge1 = new RGraph.Gauge('power', 0, <?php print $eis_device["cpower1"];?>, p1);
    //gauge1.Set('chart.title', 'current power');
    gauge1.Set('chart.red.start','150');
    gauge1.Set('chart.green.end','100');
    gauge1.Set('chart.green.color','green');
    gauge1.Set('chart.red.color','red');
    // animate gauge (to remove the effect substitute with gauge1.Draw());
    RGraph.Effects.Gauge.Grow(gauge1);
   
</script>


<script>
    // modify page in real time

    // process realtime data
    function processdata(data) {
        var d = eval( "("+data+")" );
        var i,status,img;
        // process realtime data
        for (i in d)
            switch(i) {
                case "timestamp":
                    document.getElementById('s_time').innerHTML = getfdate(d[i]);
                    break;
                case "enabled":
                    if (d[i]) status="enabled"; else status="<font color=red>disabled</font>"; 
                    document.getElementById('status').innerHTML = "("+status+")";
                    break;
                case "power":
                    if (d[i]) img="on.png"; else img="off.png"; 
                    document.getElementById('switch').innerHTML = '<img width="80" height="30" src="' + img + '">';
                    break;
                 case "cpower1":
                    gauge1.value=d[i];
                    RGraph.Effects.Gauge.Grow(gauge1);                    
                    break;
           }
    }

    // realtime processing code (long poll)
    var httpchan,data,page;
    page="<?php print $thispage;?>";
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        httpchan=new XMLHttpRequest();
    }
    else {
        // code for IE6, IE5
        httpchan=new ActiveXObject("Microsoft.XMLHTTP");
    }
    // register callback function
    httpchan.onreadystatechange=function() {
        if (httpchan.readyState==4 && httpchan.status==200) {
            processdata(httpchan.responseText);
            // reopen http channel again
            httpchan.open("GET",page+"?realtime=1",true);
            httpchan.send();
        }
    }
    // open http channel for the first time
    httpchan.open("GET",page+"?realtime=1",true);
    httpchan.send();
</script>

<script>
    // send back a name-value couple originating from this page
    // as a GET request, ignoring the return page content
    function sendback(name,value) {
        var httpchan2;
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            httpchan2=new XMLHttpRequest();
        }
        else {
            // code for IE6, IE5
            httpchan2=new ActiveXObject("Microsoft.XMLHTTP");
        }
        // send the GET request with the name-value as parameter
        httpchan2.open("GET",page+"?callback=1&"+name+"="+value,true);
        httpchan2.send();
    }
</script>


<br>
</body>
</html>
