<?php

// **** EIS ****
// standard device control page
// upf, Jun2013

// do not change this page 

// required include files
require_once("/etc/eis_conf.php");
include("private/device_conf.php");
include($eis_conf["path"]."/system/eis_device_lib.php");
include("private/device.php");

// include actual implementation
include($eis_conf["path"]."/system/eis_control_page.php");

?>
