<?php

// **** EIS ****
// global eis system configuration
// available into global var: $eis
// upf, May2013

// eis system absolute path (terminated without /)
$eis["path"]="/Users/upf/Sites/Site/devel/eis";

// real time timezone
$eis["timezone"]="Europe/Rome";

// device call timeout (seconds)
$eis["timeout"]=30;

// use base64 encoding/decoding (set to false only during debug)
$eis["base64"]=true;

// mysql database config
$eis["dbserver"]="127.0.0.1";
$eis["user"]="root";
$eis["password"]="deby21523";
$eis["dbname"]="eis";

// log file name
$eis["logfile"]=$eis["path"]."/system/eis.log";


// placeholders for system internal variables
// values will change during execution

// last error code and message init values
$eis["error"]=false;
$eis["errmsg"]="";



?>
