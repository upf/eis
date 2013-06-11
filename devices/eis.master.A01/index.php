<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Master Console</title>
 </head>
    <body>

<?php

// **** EIS ****
// eis master console page
// upf, Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_system_lib.php");
include("private/device_conf.php");

// check GET parameters
$thispage=$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
if (isset($_REQUEST["device"])) $device=$_REQUEST["device"]; else $device="";
if (isset($_REQUEST["type"])) $type=$_REQUEST["type"]; else $type="exec";
if (isset($_REQUEST["command"])) $command=$_REQUEST["command"]; else $command="command/signal  par1name par1value  par2name par2value ......";

// find local device IDs
$list=array();
foreach(scandir("../") as $d) 
    if($d[0]!="." and $d!="lib") array_push($list, $d);

// call all devices to get information on them
$info=array();
foreach ($list as $d) 
    if (eis_call(geturl($d),time(),geturl($eis_dev_conf["ID"]),"exec","getconfig",array(),$returnmsg)) {
        $info[$d]=$returnmsg["returnpar"];
        $info[$d]["enabled"]="yes";
    }
    else 
        if ($returnmsg["error"]=="system:notEnabled") {
            $info[$d]["enabled"]="no";
            $info[$d]["class"]=$info[$d]["type"]=$info[$d]["cline"]=$info[$d]["gline"]="-----";
        }
        else
            $info[$d]=false;

// print headers
print "<h2>Master: ".$eis_dev_conf["ID"]."</h2>\n";
print "<h3>local devices:</h3>\n";

// print a table of local devices
print "<form action=http://$thispage method='get'>\n";
print "<table border=1><tr><th>&nbsp deviceID &nbsp</th><th>&nbsp class &nbsp</th><th>&nbsp type &nbsp</th>\n";
print "<th>&nbsp c_line &nbsp</th><th>&nbsp g_line &nbsp</th><th>&nbsp enabled &nbsp</th></tr>\n";
foreach ($info as $d=>$i) {
    print "<tr><td>&nbsp <a href='console.php?device=$d&type=exec&command=help' target='_blank'>$d</a> &nbsp</td>";
    if ($i) {
        extract($i,EXTR_OVERWRITE);
        if (!isset($cline)) $cline="-----";
        if (!isset($gline)) $gline="-----";
    }
    else
        $enabled=$class=$type=$cline=$gline="error";
    print "<td>&nbsp $class &nbsp</td><td>&nbsp $type &nbsp</td>\n";
    print "<td>&nbsp $cline &nbsp</td><td>&nbsp $gline &nbsp</td><td>&nbsp $enabled &nbsp</td></tr>\n";    
}
print "</table>\n";



// return URL from deviceID
// actual implementation only for local devices
function geturl($deviceID) {
    return "http://localhost/eis/$deviceID";
}


?>

<br>
</body>
</html>
