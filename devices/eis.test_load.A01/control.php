<?php

// **** EIS ****
// standard device control page
// upf, May2013

// device include
include("device.php");

// get call data and parameters
$calldata=eis_getcalldata();

// execute different types of call on this device
switch ($calldata["type"]) {
	case "exec":
		$returnmsg=eis_exec($calldata);
		if ($returnmsg["error"])
			eis_send_error_msg($returnmsg["error"],$returnmsg["returnpar"]["errordata"]);
		else
			eis_send_ok_msg($returnmsg);
		break;
	case "dexec":
		eis_send_ok_msg(array("error"=>null,"returnpar"=>array()));
		$returnmsg=eis_exec($calldata);
		eis_call($calldata["from"],time(),$eis_device["ID"],"signal","dexec_return",$returnmsg,&$retmsg);
		break;
	case "signal":
		eis_send_ok_msg(array("error"=>null,"returnpar"=>array()));
		eis_signal($calldata);
		break;
	default:
		eis_send_error_msg("system:unknownCallType",$calldata["type"]); 
}


?>
