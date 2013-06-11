<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Command Console</title>
 </head>
    <body>
    <h2>Command Console (local devices)</h2>



<?php

// **** EIS ****
// eis console interface
// upf, May-Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_system_lib.php");
include("private/device_conf.php");

// check GET parameters
if (isset($_REQUEST["device"])) $device=$_REQUEST["device"]; else $device="";
if (isset($_REQUEST["type"])) $type=$_REQUEST["type"]; else $type="exec";
if (isset($_REQUEST["command"])) $command=$_REQUEST["command"]; else $command="command/signal  par1name par1value  par2name par2value ......";

// find local device IDs
$list=array();
foreach(scandir("../") as $d) 
    if($d[0]!="." and $d!="lib") array_push($list, $d);

// print form for calling
print "<form action=http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]." method='get'>\n";
print "<h3>devices&nbsp&nbsp: \n";
foreach ($list as $d) {
    if ($device==$d) $c="checked"; else $c="";
    print "&nbsp&nbsp<input type='radio' name='device' value='$d' $c> <a href='".geturl($d)."/help.php' target='_blank'>$d</a>\n";
}
print "</h3>\n";
if ($type=="exec") $c="checked"; else $c="";
print "<h3>call type:&nbsp&nbsp <input type='radio' name='type' value='exec' $c> exec\n";
if ($type=="dexec") $c="checked"; else $c="";
print " &nbsp&nbsp<input type='radio' name='type' value='dexec' $c> dexec\n";
if ($type=="signal") $c="checked"; else $c="";
print "&nbsp&nbsp<input type='radio' name='type' value='signal' $c> signal</b>\n";
print "<br><br><b>command: <input type='text' name='command' size=128 value='$command'>\n";
print "<input type='submit' value='make call'></h3>\n</form>\n";

// do a call
if ($device!="") {
    // set call parameters
    $par=explode(" ",trim($command));
    $cmd=$par[0];
    $param=array();
    for ($i=1; $i<sizeof($par); $i+=2) {
        $j=$i+1;
        if ($j<sizeof($par)) $param[$par[$i]]=$par[$j];
    }
    // set timer
    $mtime=microtime(true);
    // exec call
    if (!eis_call(geturl($device),time(),geturl($eis_dev_conf["ID"]),$type,$cmd,$param,$returnmsg)) {
        print "<h3>call ERROR</h3>\n";
        print "<b>error code:</b>&nbsp <i>".$eis_conf["error"]."</i><br><br>\n";
        print "<b>error message: </b><br>";
        print "<i>".nl2br(print_r($eis_conf["errmsg"],true))."</i><br>";
    }
    else {
        print "<h3>call OK</h3>";
        print "<b>return parameters:</b><br>\n";
        print "<i>".nl2br(print_r($returnmsg["returnpar"],true))."</i>\n";
    }
    // print execution time
    $delay=1000*(microtime(true)-$mtime);
    print "<br><i>execution time = ".sprintf("%4.3f",$delay)." msec</i>\n";
    die();
}


// return URL from deviceID
// actual implementation only for local devices
function geturl($deviceID) {
    return "http://localhost/eis/$deviceID";
}


?>

<br>
</body>
</html>
