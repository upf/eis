<?php
// **** EIS ****
// eis system interface page
// upf, Jun2013

// exec console command and die
if (isset($_REQUEST["exec"])) {
	require_once("/etc/eis_conf.php");
	include($eis_conf["path"]."/system/eis_system_lib.php");
	$l=str_replace("  "," ",$_REQUEST["exec"]);
	for($i=0;$i<10;$i++) $l=str_replace("  "," ",$l);
	$a=explode(" ",$l);
	$param=array();
	for ($i=3; $i<sizeof($a); $i+=2) {
		$j=$i+1;
		if ($j<sizeof($a)) $param[$a[$i]]=$a[$j];
	}
	$mtime=microtime(true);
	eis_call("http://".$_SERVER["SERVER_NAME"]."/eis/".$a[0],time(),"eis.system",$a[1],$a[2],$param,$returnmsg);
	if (!$eis_error) 
		$console="call OK:\n".print_r($returnmsg["returnpar"],true);
	else 
		$console="call ERROR:\n\n$eis_error\n\n$eis_errmsg\n";
	$delay=1000*(microtime(true)-$mtime);
	$console=$console."\nexecution time = ".sprintf("%4.3f",$delay)." msec\n";
	$logtext="";
	usleep(5000);
	foreach (eis_log_get("",50) as $l) $logtext=$logtext.$l."\n";
	print json_encode(array("console"=>$console,"log"=>$logtext));
	die();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>eis system</title>
    <link rel="stylesheet" type="text/css" href="lib/eis.css">
 </head>
 <script>
	// create an http channel for page calling
	if (window.XMLHttpRequest)
		httpchan=new XMLHttpRequest();  // for IE7+, Firefox, Chrome, Opera, Safari
    else 
        httpchan=new ActiveXObject("Microsoft.XMLHTTP");  // for IE6, IE5
    // register callback function
    httpchan.onreadystatechange=updateconsole;


	// manage the return key pressed event
	function onTestChange() {
	    var key = window.event.keyCode;
	    if (key == 13) {
		    // call back php page through the http channel
		    httpchan.open("GET","index.php?exec="+document.getElementById("command").value,true);
		    httpchan.send();
	        return false;
	    }
	    else {
	        return true;
	    }
	}

	// update console field
	function updateconsole() {
        if (httpchan.readyState==4 && httpchan.status==200) {
        	data=eval( "("+httpchan.responseText+")" );
    		document.getElementById("console").value=data["console"];
    		document.getElementById("log").value=data["log"];
    	}
	}

 </script>
 <body>

<?php

// print page headers
print "<h2>eis system <i>(".gethostname().")</i></h2>\n";

// check eis system config file installation
if (!file_exists("/etc/eis_conf.php"))
	die("<h3>---> eis system not installed or wrong installation, please install</h3>");
require_once("/etc/eis_conf.php");

// check eis path correct configuration
if (!file_exists($eis_conf["path"]."/system/eis_conf.php"))
	die("<h3>---> uncorrect eis path: edit /etc/eis_conf.php or reinstall</h3>");

// check mysql server, username, password and db
$mysqli = new mysqli($eis_conf["dbserver"],$eis_conf["user"],$eis_conf["password"],$eis_conf["dbname"]);
if ($mysqli->connect_errno) 
	die("<h3>---> cannot connect to database: check mysql installation and then edit /etc/eis_conf.php or reinstall</h3>");

// check eis system version, date and author
$config=file($eis_conf["path"]."/system/eis_conf.php");
$version=$date=$author="";
foreach ($config as $line) {
	if ($f=getfield($line,"version")) $version=$f;
	if ($f=getfield($line,"date")) $date=$f;
	if ($f=getfield($line,"author")) $author=$f;
}
if ($eis_conf["version"]!=$version or $eis_conf["date"]!=$date or $eis_conf["author"]!=$author)
	die("<h3>---> eis system version,date or author changed, please reinstall</h3>"); 

// required includes
include($eis_conf["path"]."/system/eis_system_lib.php");

// current page url
$page="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];

// print current version, date and author
print "<i>version $version &nbsp($date, $author)</i><br>\n";

// read device database data
$devdb=eis_getdevices("all");
if ($eis_error) die("<br>cannot read database: ".$eis_error." --> ".$eis_errmsg);
$devinfo=$devdb;

// perform a new scan as default
if (!isset($_REQUEST["noscan"])) {
	// get local device info
	$devinfo=get_local_device_info();
	// check and/or set the installation state 
	reset($devinfo);
	foreach ($devinfo as $d=>$i) {
		if (array_key_exists($d,$devdb)) {
			if ($i["version"]!=$devdb[$d]["version"] or $i["date"]!=$devdb[$d]["date"] or $i["author"]!=$devdb[$d]["author"])
				$devinfo[$d]["installed"]="no";
			else 
				$devinfo[$d]["installed"]=$devdb[$d]["installed"];
			$devinfo[$d]["published"]=$devdb[$d]["published"];			
		}
		else {
			$devinfo[$d]["installed"]="no";
			$devinfo[$d]["published"]="no";			
		}
	}
}

// manage required actions
if (isset($_REQUEST["action"])) 
	switch($_REQUEST["action"]) {
		// clear log 
		case "clearlog":
			eis_log_clear();
			break;
		// install one device
		case "install":
			if (isset($_REQUEST["id"])) {
				$id=$_REQUEST["id"];
				if (eis_call("http://".$_SERVER["SERVER_NAME"]."/eis/$id",time(),"eis.system","exec","install",array(),$returnmsg))
					$devinfo[$id]["installed"]="yes";
				else
					print "<br>cannot install $id: $eis_error  $eis_errmsg";
			}
			break;
		// install all devices
		case "installall":
			reset($devinfo);
			foreach ($devinfo as $d=>$i) 
				if ($devinfo[$d]["installed"]=="no")
					if (eis_call("http://".$_SERVER["SERVER_NAME"]."/eis/$d",time(),"eis.system","exec","install",array(),$returnmsg))
						$devinfo[$d]["installed"]="yes";
					else
						print "<br>cannot install $d: $eis_error  $eis_errmsg";
			break;
	}


// write device database data (only for a new scan)
if (!isset($_REQUEST["noscan"])) {
	$mysqli->query("DELETE FROM devices");
	reset($devinfo);
	foreach ($devinfo as $d=>$i) {
		$query="INSERT INTO devices VALUES ('$d','".$i["version"]."','".$i["date"]."','".$i["author"]."','".$i["class"]."','".
				$i["type"]."','".$i["ifport"]."','".$i["description"]."','".$i["cpower"]."','".$i["gpower"]."','".$i["published"]."','".$i["installed"]."')";
		if (!$mysqli->query($query)) die("cannot write database: ".$mysqli->error);
	}
}

// print local device data table
$headers=array("device","installed","published","version","date","author","class","type","port","description");
$rows=array();
reset($devinfo);
foreach ($devinfo as $d=>$i) {
	if ($i["installed"]=="yes") $installed="yes"; else $installed="<a href='$page?id=$d&action=install'>install</a>";
	if ($i["installed"]=="yes") $device="<a href='http://".$_SERVER["SERVER_NAME"]."/eis/$d' target='_blank'>$d</a>"; else $device=$d;
	if ($i["published"]=="yes")
		$published="<a href='$page?id=$d&action=hide'>yes</a>";
	else
		//$published="<a href='$page?id=$d&action=show'>no</a>";
		$published="no";
	$rows[]=array($device,$installed,$published,$i["version"],$i["date"],$i["author"],$i["class"],$i["type"],$i["ifport"],$i["description"]);	
}
print_datatable("Local devices:",$headers,$rows);
print "<button onClick='window.location.href=\"$page\"'>rescan</button>\n";
print " &nbsp&nbsp<button onClick='window.location.href=\"$page?action=installall\"'>install all</button>\n";

// ask for opening a console
print "<br><br><b>Command line console:</b><br>\n";
print "<input type='text' id='command' size=150 onkeypress=\"onTestChange();\" 
		value='<deviceID>  <exec,dexec or signal>  <command>  <param1 name> <param1 value>  <param2 name> <param2 value>  .....'><br>";
print "<textarea id='console' cols=128 rows=10></textarea>";

// show system logs
$logtext="";
foreach (eis_log_get("",50) as $l) $logtext=$logtext.$l."\n";
print "<br><br><b>Last Local System Logs:</b>\n";
print "<br><textarea id='log' cols=128 rows=10 readonly>$logtext</textarea><br>";
print "<button onClick='window.location.href=\"$page?noscan=1\"'>refresh</button>\n";
print " &nbsp&nbsp<button onClick='window.location.href=\"$page?action=clearlog&noscan=1\"'>clear logs</button>\n";


print "</body></html>\n";




// ******************** functions *************************

// utility function for printing a table with its title, headers and fields
// if a field starts with @ it is treated as data, else it is treated as an ID of an empty div 
function print_datatable($title,$headers,$rows) {
	print "<br><b>$title</b><br>\n";
	print "	<table border=1><tr>";
	foreach ($headers as $f) print "<th>&nbsp $f &nbsp</th>";
	print "</tr>\n";
	foreach ($rows as $row) {
		print "<tr>";
		foreach ($row as $j=>$f) {
			if ($j==0) $a="left"; else $a="center";
			print "<td style='text-align:$a'>&nbsp $f &nbsp</td>";
		}
		print "</tr>";
	}
	print "</table>\n";
}

// find all devices and hosts returning an associative array as deviceIDs => host
// if $network is true scan the network else search only in localhost
function get_local_device_info() {
	global $eis_conf;
	// find localhost devices
	$device=array();
	foreach(scandir("./") as $d) 
    	if($d[0]!="." and $d!="lib" and $d!="index.php") $device[]=$d;
    // scan each device config file for info
	$info=array();
	foreach ($device as $d) {
		$eis_dev_conf=array();
		include($d."/private/device_conf.php");
		$info[$d]["class"]=$eis_dev_conf["class"];
		$info[$d]["type"]=$eis_dev_conf["type"];
		$info[$d]["version"]=$eis_dev_conf["version"];
		$info[$d]["date"]=$eis_dev_conf["date"];
		$info[$d]["author"]=$eis_dev_conf["author"];
		$info[$d]["description"]=$eis_dev_conf["description"];
		$info[$d]["ifport"]=$eis_dev_conf["ifport"];
		if (array_key_exists("cpower1",$eis_dev_conf)) $cpower1=$eis_dev_conf["cpower1"]; else $cpower1=0;
		if (array_key_exists("cpower2",$eis_dev_conf)) $cpower2=$eis_dev_conf["cpower2"]; else $cpower2=0;
		if (array_key_exists("cpower3",$eis_dev_conf)) $cpower3=$eis_dev_conf["cpower3"]; else $cpower3=0;
		$info[$d]["cpower"]="$cpower1,$cpower2,$cpower3";
		if (array_key_exists("gpower1",$eis_dev_conf)) $gpower1=$eis_dev_conf["gpower1"]; else $gpower1=0;
		if (array_key_exists("gpower2",$eis_dev_conf)) $gpower2=$eis_dev_conf["gpower2"]; else $gpower2=0;
		if (array_key_exists("gpower3",$eis_dev_conf)) $gpower3=$eis_dev_conf["gpower3"]; else $gpower3=0;
		$info[$d]["gpower"]="$gpower1,$gpower2,$gpower3";
		}
	return $info;
}

// utility function for finding a specific field into an eis_conf.php line
function getfield($line,$field) {
	$line=ltrim($line);
	$str='$eis_conf["'.$field.'"]=';
	$s=strpos($line,$str);
	if ($s!==false) return strtok(trim(substr($line,strlen($str))),"\"");
	return false;
}


?>
