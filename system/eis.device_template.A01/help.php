<?php

// **** EIS ****
// eis standard device help page
// upf, Jun2013

// do not change this page 

// required includes
require_once("/etc/eis_conf.php");
include("private/device_conf.php");

// include actual implementation
include($eis_conf["path"]."/system/eis_help_page.php");

?>
