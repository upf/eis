<?php

// **** EIS ****
// standard device control page implementation (see device's control.php)
// upf, May2013

// get call data and parameters
$calldata=eis_getcalldata();

// execute different types of call on this device
switch ($calldata["type"]) {
	case "exec":
		$returnmsg=eis_exec($calldata);
		eis_send_returnmsg($returnmsg);
		break;
	case "dexec":
		eis_send_returnmsg(eis_ok_msg(null));
		$returnmsg=eis_exec($calldata);
		eis_call($calldata["from"],time(),$eis_dev_conf["ID"],"signal","dexec_return",$returnmsg,$retmsg);
		break;
	case "signal":
		eis_send_returnmsg(eis_ok_msg(null));
		eis_signal($calldata);
		break;
	default:
		eis_send_returnmsg(eis_error_msg("system:unknownCallType",$calldata["type"])); 
}


?>
