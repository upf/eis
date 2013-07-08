<?php

// **** EIS ****
// global eis system configuration
// available into global var: $eis_conf

// version, date and author of this eis system
$eis_conf["version"]="0.0.1";
$eis_conf["date"]="2013-06-25";
$eis_conf["author"]="upf";

// eis folder absolute path (terminated without /)
$eis_conf["path"]="xxxxx";

// the name of this host (set differently only if virtualhost is used)
$eis_conf["host"]=$_SERVER["SERVER_NAME"];

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

?>
