<?php

// **** EIS ****
// global eis system configuration
// available into global var: $eis
// upf, May2013

// eis folder absolute path (terminated without /)
$eis_conf["path"]="xxxxx";

// real time timezone
$eis_conf["timezone"]="Europe/Rome";

// device alive timeouts (seconds)
$eis_conf["atimeout"]=2;

// device call timeouts (seconds)
$eis_conf["timeout"]=30;

// use base64 encoding/decoding (set to false only during debug)
$eis_conf["base64"]=true;

// mysql database config
$eis_conf["dbserver"]="127.0.0.1";	// write mysql server address here 
$eis_conf["user"]="xxxx";			// write mysql username here
$eis_conf["password"]="yyyy";		// write mysql password here
$eis_conf["dbname"]="eis";

// log file name
$eis_conf["logfile"]=$eis_conf["path"]."/system/eis.log";


// placeholders for system internal variables
// values will change during execution

// last error code and message init values
$eis_conf["error"]=false;
$eis_conf["errmsg"]="";



?>
