#!/usr/bin/php -q
<?php

// **** EIS ****
// call a given device with some parameters
// upf, May2013

// get command line parameter
if (sizeof($argv)<4) {
	print "usage:  eiscall url type command par1name par1value par2name par2value ......\n";
	exit(0);
}
$url=$argv[1]."/control.php";
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
