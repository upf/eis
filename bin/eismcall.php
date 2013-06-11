#!/usr/bin/php -q
<?php

// **** EIS ****
// DNS-SD eis call
// upf, Jun2013

// find the hosts which publish the _eis-info._tcp service
$handle = popen('dns-sd -B _eis-info._tcp &', 'r');
$read = fread($handle, 4096);
pclose($handle);
$line=explode("\n",$read);
$host=array();
for($i=4; $i<sizeof($line); $i++) {
	$h=explode(" ",preg_replace('!\s+!', ' ', $line[$i]));
	if (isset($h[6])) array_push($host,$h[6]);
}
$ps=array();
exec("ps ax | grep _eis-info._tcp",$ps);
$pid=intval($ps[0]);
exec("kill -KILL $pid");

// find the devices published by each host
$device=array();
foreach($host as $h) {
	$handle = popen('dns-sd -L '.$h.' _eis-info._tcp &', 'r');
	$read = fread($handle, 4096);
	pclose($handle);
	$line=explode("\n",$read);
	if (sizeof($line)>3) {
		$txt=explode(" ",trim($line[4]));
		foreach($txt as $id) $device[$id]=$h.".local";    // to do, check for duplicate here !
	}
	$ps=array();
	exec("ps ax | grep _eis-info._tcp",$ps);
	$pid=intval($ps[0]);
	exec("kill -KILL $pid");
}

//print_r($device);

// get command line parameter
if (sizeof($argv)<4) {
	print "usage:  eismcall deviceID type command par1name par1value par2name par2value ......\n";
	exit(0);
}
if (!array_key_exists($argv[1], $device)) die("cannot find the requested device on the network");
$url="http://".$device[$argv[1]]."/eis/".$argv[1]."/control.php";
$type=$argv[2];
$cmd=$argv[3];
$param=array();
for ($i=4; $i<sizeof($argv); $i+=2) {
	$j=$i+1;
	if ($j<sizeof($argv)) $param[$argv[$i]]=$argv[$j];
}

// required includes
require_once("/etc/eis.conf");
include($eis["path"]."/system/eis_system_lib.php");

// set timer
$mtime=microtime(true);

// exec call
if (!eis_call($url,time(),"console",$type,$cmd,$param,$returnmsg)) {
	print "error code   : ".$eis["error"]."\n";
	print "error message: ";
	print_r($eis["errmsg"]);
}
else {
	print "return parameters:\n";
	print_r($returnmsg["returnpar"]);
}

// print execution time
$delay=1000*(microtime(true)-$mtime);
print "\nexecution time = ".sprintf("%4.3f",$delay)." msec\n\n";

?>
