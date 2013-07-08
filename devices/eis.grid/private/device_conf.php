<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";
$eis_dev_conf["date"]="2013-06-17";
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40500;

// device class (functional type)
$eis_dev_conf["class"]="grid";

// device short description
$eis_dev_conf["description"]="electrical grid connection";

// device electrical type (load, generator, load&gen)
$eis_dev_conf["type"]="load&gen";

// max grid immission power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["cpower1"]=3000;
$eis_dev_conf["cpower2"]=3000;
$eis_dev_conf["cpower3"]=3000;

// max grid consumption power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["gpower1"]=3000;
$eis_dev_conf["gpower2"]=3000;
$eis_dev_conf["gpower3"]=3000;

// device status array
// these value will be used during device initialization
$eis_dev_conf["status"]=array(
	// grid immission vars
	"cline"		=> "unprotected",		// set always to "unprotected"
	"cpower1"	=> 0,					// current immission power on  line 1
	"cenergy1"	=> 0, 					// current total sold energy by line 1
	"cpower2"	=> 0,					// current immission power on  line 2
	"cenergy2"	=> 0, 					// current total sold energy by line 2
	"cpower3"	=> 0,					// current immission power on  line 3
	"cenergy3"	=> 0, 					// current total sold energy by line 3
	// grid consumption vars
	"gline"		=> "unprotected",		// set always to "unprotected"
	"gpower1"	=> 0,					// current assorbed power on line 1
	"genergy1"	=> 0, 					// current total assorbed energy by line 1
	"gpower2"	=> 0,					// current assorbed power on line 2
	"genergy2"	=> 0, 					// current total assorbed energy by line 2
	"gpower3"	=> 0,					// current assorbed power on line 3
	"genergy3"	=> 0, 					// current total assorbed energy by line 3
	// prices
	"price_model"=> "constant_rate",	// price model ID
	"price_sell" => 0.01,				// current selling price
	"price_buy"  => 0.02,				// current buying price
	"total_sell" => 0,					// current cost for buying
	"total_buy"	 => 0					// current gain for selling
	
);



?>
