<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

/////////// mandatory configuration fields ///////////

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.3";
$eis_dev_conf["date"]="2013-07-24";
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device (terminated without /)
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40500;

// device class (functional type)
$eis_dev_conf["class"]="grid";

// device short description
$eis_dev_conf["description"]="configurable electrical grid connection";

// device electrical type (load, generator, load&gen)
$eis_dev_conf["type"]="load&gen";


/////////// available configurations ///////////

// available price models
$eis_dev_conf["priceIDs"]=array(
	"ESE-1price-small"=>"Enel Servizi Elettrici tariffa monoraria 3kW ITA",
	"ESE-2prices-small"=>"Enel Servizi Elettrici tariffa bioraria 3kW ITA",
	"ESE-1price-large"=>"Enel Servizi Elettrici tariffa monoraria >6kW ITA",
	"ESE-2prices-large"=>"Enel Servizi Elettrici tariffa bioraria >6kW ITA"
);

// config = specific configuration description
// gpowerX = max generation power for phase X (in watts), set to zero for unused phase(s)
// cpowerX = max consumption power for phase X (in watts), set to zero for unused phase(s)
// price = price model ID

$eis_dev_conf["configurations"]=array(
	// small 3kW 1 phase connection with ITA constant price plan
	"ESE_3kW_1P_1price" => array (
		"config"=>"3kW 1P ITA Enel Servizi Elettrici tariffa monoraria",
		"gpower1"=>3000,
		"gpower2"=>0,
		"gpower3"=>0,
		"cpower1"=>3000,
		"cpower2"=>0,
		"cpower3"=>0,
		"price"=>"ESE-1price-small"
	),
	// small 3kW 1 phase connection with ITA 2-prices plan
	"ESE_3kW_1P_2prices" => array (
		"config"=>"3kW 1P ITA Enel Servizi Elettrici tariffa bioraria",
		"gpower1"=>3000,
		"gpower2"=>0,
		"gpower3"=>0,
		"cpower1"=>3000,
		"cpower2"=>0,
		"cpower3"=>0,
		"price"=>"ESE-2prices-small"
	),
	// medium 9kW 3 phase connection with ITA constant price plan
	"ESE_9kW_1price" => array (
		"config"=>"9kW 3P ITA Enel Servizi Elettrici tariffa monoraria",
		"gpower1"=>3000,
		"gpower2"=>3000,
		"gpower3"=>3000,
		"cpower1"=>3000,
		"cpower2"=>3000,
		"cpower3"=>3000,
		"price"=>"ESE-1price-large"
	),
	// medium 9kW 3 phase connection with ITA 2-prices plan
	"ESE_9kW_2prices" => array (
		"config"=>"9kW 3P ITA Enel Servizi Elettrici tariffa bioraria",
		"gpower1"=>3000,
		"gpower2"=>3000,
		"gpower3"=>3000,
		"cpower1"=>3000,
		"cpower2"=>3000,
		"cpower3"=>3000,
		"price"=>"ESE-2prices-large"
	),
	// large 20kW 3 phase connection with ITA constant price plan
	"ESE_20kW_1price" => array (
		"config"=>"20kW 3P ITA Enel Servizi Elettrici tariffa monoraria",
		"gpower1"=>6666,
		"gpower2"=>6666,
		"gpower3"=>6666,
		"cpower1"=>6666,
		"cpower2"=>6666,
		"cpower3"=>6666,
		"price"=>"ESE-1price-large"
	),
	// large 20kW 3 phase connection with ITA 2-prices plan
	"ESE_20kW_2prices" => array (
		"config"=>"20kW 3P ITA Enel Servizi Elettrici tariffa bioraria",
		"gpower1"=>6666,
		"gpower2"=>6666,
		"gpower3"=>6666,
		"cpower1"=>6666,
		"cpower2"=>6666,
		"cpower3"=>6666,
		"price"=>"ESE-2prices-large"
	),
);

// device status array
// these value will be used during device initialization
$eis_dev_conf["status"]=array(
	// set default configuration
	"configID" => "ESE_3kW_1P_1price",
	// grid connection status var
	"gridstatus"=>"ok",			// "ok","disconnected","overload","overgen" (last 3 == no grid connection)
	// grid immission vars (consuming on unprotected line == selling to grid)
	"cline"		=> "unprotected",		// set always to "unprotected"
	"cpower1"	=> 0,			// current immission power on  line 1
	"cenergy1"	=> 0, 			// current total sold energy by line 1
	"cpower2"	=> 0,			// current immission power on  line 2
	"cenergy2"	=> 0, 			// current total sold energy by line 2
	"cpower3"	=> 0,			// current immission power on  line 3
	"cenergy3"	=> 0, 			// current total sold energy by line 3
	// grid consumption vars (generating on unprotected line == buying from grid)
	"gline"		=> "unprotected",		// set always to "unprotected"
	"gpower1"	=> 0,			// current assorbed power on line 1
	"genergy1"	=> 0, 			// current total assorbed energy by line 1
	"gpower2"	=> 0,			// current assorbed power on line 2
	"genergy2"	=> 0, 			// current total assorbed energy by line 2
	"gpower3"	=> 0,			// current assorbed power on line 3
	"genergy3"	=> 0, 			// current total assorbed energy by line 3
	// prices
	"price_buy" => 0,			// current price for buying
	"price_sell" => 0,			// current price for selling
	"total_sell" => 0,			// current cost for buying
	"total_buy"	 => 0			// current gain for selling
	
);



?>
