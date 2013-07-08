<?php

// **** EIS ****
// eis device interface lib
// upf, Jun2013

// required includes
include("private/device_conf.php");
include($eis_conf["path"]."/system/eis_device_lib.php");

// get and init status
if (!eis_load_status()) die ($eis_error." --> ".$eis_errmsg);

// init realtime interface global variables
$eis_oldstatus=$eis_dev_status;
$eis_realtime_header="";


//////////// interface functions ///////////

// return the current page url
function eis_page_url() {
	 return "http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
}

// check if there is a callback call with parameter $name
// return true is there is, false otherwise
// $name value can be found into $_REQUEST[$name]
function eis_callback($name) {
	return (isset($_REQUEST["callback"]) and isset($_REQUEST[$name]));
}

// create a realtime interface handler at port $eis_dev_conf["ifport"];
// listen for reload on a socket server and send changed status values as Json array to the page Javascript
// $oldstatus must contain the oldstatus array that will be update when a new reload command is received
function eis_realtime_handler() {
	global $eis_dev_status,$eis_dev_conf,$eis_error,$eis_errmsg;
	global $eis_oldstatus,$eis_realtime_header;
	$eis_realtime_header='<script src="../lib/eis_realtime.js"></script>';
	if (isset($_REQUEST["realtime"])) {
	    // init UDP server
	    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
	    socket_bind($socket,'127.0.0.1',$eis_dev_conf["ifport"]);
	    if (!$socket) die("Fatal error: ". socket_last_error());
	    // wait for status reload signal
	    while (true) {
	        socket_recvfrom($socket, $d, 6, 0, $a,$p);
	        // send realtime data if any
	        if ($d=="reload") {
	            if (!eis_load_status()) die ($eis_error." --> ".$eis_errmsg);
	            $changed=array();
	            foreach($eis_dev_status as $key=>$value) if ($value!=$eis_oldstatus[$key]) $changed[$key]=$value;
	            print json_encode($changed);
	            break;
	        }
	    }
	    socket_close($socket);
	    die();
	}
}


// return a string conaining $space HTML spaces
function eis_spaces($spaces) {
	if ($spaces<0) $spaces=0;
	$sp="";
	for($i=0;$i<$spaces;$i++) $sp=$sp."&nbsp ";
	return $sp;
}

// return a string containing the standard HTML header with some eis feature
function eis_page_header($title,$headers) {
	global $eis_conf,$eis_dev_conf,$eis_realtime_header;
	return
'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>'.$title.'</title>
    <link rel="stylesheet" type="text/css" href="../lib/eis.css">
    '.$eis_realtime_header.$headers.'
 </head>
<body>
 <a href="'.eis_dev_geturl("","").'">eis home</a> &nbsp&nbsp
 <a href="'.eis_dev_geturl($eis_dev_conf["ID"],"").'"> '.$eis_dev_conf["ID"].' home</a> &nbsp&nbsp
 <a href="'.eis_dev_geturl($eis_dev_conf["ID"],"").'/help.php"> '.$eis_dev_conf["ID"].' help</a>
 <h2>'.$eis_dev_conf["ID"].' <i>('.$_SERVER["SERVER_NAME"].')</i></h2>
 ';
}

// function for printing a table with its title, headers and fields
// $header is an array of header strings
// $rows is an array of table row, each is an array of fields
// first column is left aligned, the others are centered 
function eis_print_datatable($title,$headers,$rows,$options) {
	print "<br><b>$title</b><br>\n";
	print "	<table border=1><tr>";
	foreach ($headers as $f) print "<th>&nbsp $f &nbsp</th>";
	print "</tr>\n";
	foreach ($rows as $k=>$row) {
		print "<tr id='$k'>";
		foreach ($row as $j=>$f) {
			if ($j==0) $a="left"; else $a="center";
			print "<td style='text-align:$a'>&nbsp $f &nbsp</td>";
		}
		print "</tr>\n";
	}
	print "</table>\n";
}


//////// other here




?>
