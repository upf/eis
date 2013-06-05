<?php

// **** EIS ****
// global eis system configuration
// available into global var: $eis
// upf, May2013

// eis folder absolute path (terminated without /)
$eis["path"]="xxxxx";

// real time timezone
$eis["timezone"]="Europe/Rome";

// device call timeout (seconds)
$eis["timeout"]=30;

// use base64 encoding/decoding (set to false only during debug)
$eis["base64"]=true;

// mysql database config
$eis["dbserver"]="127.0.0.1";	// write mysql server address here 
$eis["user"]="xxxx";			// write mysql username here
$eis["password"]="yyyy";		// write mysql password here
$eis["dbname"]="eis";

// log file name
$eis["logfile"]=$eis["path"]."/system/eis.log";


// placeholders for system internal variables
// values will change during execution

// last error code and message init values
$eis["error"]=false;
$eis["errmsg"]="";



?>
