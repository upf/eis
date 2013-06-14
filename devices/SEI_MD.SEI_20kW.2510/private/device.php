<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device initialization function
// return true on success, false on failure
function eis_device_init() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	eis_clear_error();
	// put specific device initialization code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call

	return true;
}

// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "simulate":
			if (!array_key_exists("timestamp",$callparam)) return eis_error_msg("SEI_MD.SEI_20kW.2510:parameterMissing","timestamp");
			if (!array_key_exists("wind_speed",$callparam)) return eis_error_msg("SEI_MD.SEI_20kW.2510:parameterMissing","wind_speed");
			if (!array_key_exists("connection",$callparam)) return eis_error_msg("SEI_MD.SEI_20kW.2510:parameterMissing","connection");
			$timestep=$callparam["timestamp"]-$eis_dev_status["timestamp"];
			// aggiornamento energy in kWh
			$eis_dev_status["genergy1"] = $eis_dev_status["genergy1"] + $eis_dev_status["gpower1"]*$timestep/3600000;
			$eis_dev_status["genergy2"] = $eis_dev_status["genergy2"] + $eis_dev_status["gpower2"]*$timestep/3600000;
			$eis_dev_status["genergy3"] = $eis_dev_status["genergy3"] + $eis_dev_status["gpower3"]*$timestep/3600000;
			
			// aggiornamento timestamp
			$eis_dev_status["timestamp"]=$callparam["timestamp"];
			
			// definizione array di valori utili per il calcolo delle gpower
			$param[] = $callparam["wind_speed"] ;
			$param[] = $callparam["connection"] ;
			// nel caso di connessione off-grid bisogna conoscere la potenza richiesta sulle singole fasi
			if ($callparam["connection"] == 'offgrid'){
			if (!array_key_exists("cpower1",$callparam)) return eis_error_msg("SEI_MD.SEI_20kW.2510:parameterMissing","cpower1");
			if (!array_key_exists("cpower2",$callparam)) return eis_error_msg("SEI_MD.SEI_20kW.2510:parameterMissing","cpower2");
			if (!array_key_exists("cpower3",$callparam)) return eis_error_msg("SEI_MD.SEI_20kW.2510:parameterMissing","cpower3");
			$param[] = $callparam["cpower1"] ;
			$param[] = $callparam["cpower2"] ;
			$param[] = $callparam["cpower3"] ;
			}
			
			// aggiornamento gpower1/2/3	
			if ($eis_dev_status["power"] ) compute_totalpower($param);
			
			// return updated status
			return eis_ok_msg($eis_dev_status);
			break;		
		// put other commands and related code here
		// case "mycommand":
				// your command code here
				// return success: $returnmsg=array("error"=>null, "returnpar"=>yourreturnarray);
				// return failure: $returnmsg=array("error"=>yourerror,"returnpar"=>array("errordata"=>yourerrormsg));
				// break;

		default:
			// manage unknown command
			$returnmsg=eis_error_msg("system:unknownCommand",$calldata["cmd"]);
	}
	return $returnmsg;
}


// process device specific signals
// return nothing
function eis_device_signal($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "poweron":
				// power on and set other value
				$eis_dev_status["power"]=true;
				$param[] = $eis_dev_status["wind_speed"];
				$param[] = $eis_dev_status["connection"];
				if ($eis_dev_status["connection"] == 'offgrid'){
				$param[] = $eis_dev_status["cpower1"];
				$param[] = $eis_dev_status["cpower2"];
				$param[] = $eis_dev_status["cpower3"];
				}
				compute_totalpower($param);
				break;
		case "poweroff":
			// power off and set other var
				$eis_dev_status["power"]=false;
				$eis_dev_status["gpower1"] = 0;
				$eis_dev_status["gpower2"] = 0;
				$eis_dev_status["gpower3"] = 0;
				
				break;
		// put other signals and related code here
		// case "mysignal":
				// your signal code here
				// in case of error, use eis_log() to log error
				// break;

		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 

// calcola le potenze sulle singole fasi in base alla velocità del vento
function compute_totalpower($param) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$wind_speed = $param[0];
	$connection = $param[1];
	$textfile = fopen("/home/vitofusco/GitHub/eis/devices/SEI_MD.SEI_20kW.2510/private/SEI_20kW_Power_curve.txt", "r") ;
	while ( $rigafile = fgets($textfile,1024)){
	$data[] = $rigafile;
	}
	fclose($textfile);

	// $data[0] contiene le velocità del vento di cut-in,cut-off 
	// e il tempo con cui è stata campionata la curva di potenza
	list($x_min,$x_max,$sample_time) = explode(";", $data[0]);


	if ($wind_speed<$x_min || $wind_speed>$x_max ) $gpower=0;
	else {
		
		for($i=1; $i<sizeof($data); $i++){
		list($id,$x,$y) = explode(";", $data[$i]);
		if ( $wind_speed == $x ) $gpower= $y; 
		}
	
		if (!isset($gpower)){
			$difference = $sample_time + 1 ;
			$i = 1;
			while($difference>$sample_time || $difference<0){
						
					list($id_riga,$X,$Y) = explode(";",$data[$i]);
					$difference = $X - $wind_speed ;
					$i++;
			}
		
		list($id,$x,$y) = explode(";", $data[$id_riga-1]);
		$x_ws =  $wind_speed ;
			
		$y_power = $y + (($x_ws-$x)/($X-$x)) * ($Y-$y);
		$gpower = $y_power;
		}
	}
	
	// HP: la potenza si ripartisce equamente sulle tre fasi
	$gpower1 = round($gpower/3, 0);
	// verifica se è connesso 'on-grid' oppure 'off-grid'
	// on-grid ---> essendo connessa alla rete elettrica non
	//              bisogna considerare l'assorbimento totale;
	// off-grid---> bisogna considerare l'assorbimento 
	//              richiesto dalle singole fasi;
	if ($connection == 'ongrid'){
		$eis_dev_status["gpower1"] = $gpower1;
		$eis_dev_status["gpower2"] = $gpower1;
		$eis_dev_status["gpower3"] = $gpower1;	
	}
	if ($connection == 'offgrid'){
		$cpower1 = $param[2];
		$cpower2 = $param[3];
		$cpower3 = $param[4];
		if ( $gpower1>$cpower1 ) $eis_dev_status["gpower1"] = $cpower1;
			else $eis_dev_status["gpower1"] = $gpower1;
		// la potenza sulle tre fasi è considerata uguale
		if ( $gpower1>$cpower2 ) $eis_dev_status["gpower2"] = $cpower2;
			else $eis_dev_status["gpower2"] = $gpower1;
		if ( $gpower1>$cpower3 ) $eis_dev_status["gpower3"] = $cpower3;
			else $eis_dev_status["gpower3"] = $gpower1;	
	// aggiornamento potenza richiesta sulle tre fasi	
	$eis_dev_status["cpower1"] = $cpower1;
	$eis_dev_status["cpower2"] = $cpower2;
	$eis_dev_status["cpower3"] = $cpower3;	
		
	}
	
	$eis_dev_status["wind_speed"] = $wind_speed;
	$eis_dev_status["connection"] = $connection;
	
							
					
}



?>
