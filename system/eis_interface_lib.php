<?php

// **** EIS ****
// eis device interface lib
// upf, Jun2013

// required includes
include("private/device_conf.php");
include($eis_conf["path"]."/system/eis_device_lib.php");

// get and init status
if (!eis_load_status()) die ($eis_error." --> ".$eis_errmsg);

// init realtime interface global variables
$eis_oldstatus=$eis_dev_status;
$eis_realtime_header="";
$eis_realtime_counter=0;


//////////// interface functions ///////////

// return the current page url
function eis_page_url() {
	 return "http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
}

// check if there is a callback call with parameter $name
// return true is there is, false otherwise
// $name value can be found into $_REQUEST[$name]
function eis_callback($name) {
	return (isset($_REQUEST["callback"]) and isset($_REQUEST[$name]));
}

// create a realtime interface handler at port $eis_dev_conf["ifport"];
// listen for reload on a socket server and send changed status values as Json array to the page Javascript
// $oldstatus must contain the oldstatus array that will be update when a new reload command is received
// must be called before eis_realtime_widgets(), eis_page_header() and widget calls
function eis_realtime_handler() {
	global $eis_dev_status,$eis_dev_conf,$eis_error,$eis_errmsg;
	global $eis_oldstatus,$eis_realtime_header;
	$eis_realtime_header='<script src="../lib/eis_realtime.js"></script>';
	if (isset($_REQUEST["realtime"])) {
	    // init UDP server
	    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
	    socket_bind($socket,'127.0.0.1',$eis_dev_conf["ifport"]);
	    if (!$socket) die("Fatal error: ". socket_last_error());
	    // wait for status reload signal
	    while (true) {
	        socket_recvfrom($socket, $d, 6, 0, $a,$p);
	        // send realtime data if any
	        if ($d=="reload") {
	            if (!eis_load_status()) die ($eis_error." --> ".$eis_errmsg);
	            $changed=array();
	            $changed["blackout"]=$eis_dev_status["blackout"];  // leave this due to a strange behaviour
	            foreach($eis_dev_status as $key=>$value) if ($value!=$eis_oldstatus[$key]) $changed[$key]=$value;
	            print json_encode($changed);
	            break;
	        }
	    }
	    socket_close($socket);
	    die();
	}
}

// initialize the realtime widget subsystem
// must be called after eis_realtime_handler() and before eis_page_header() and widget calls
function eis_realtime_widgets() {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_header;
	$thisdevice=$eis_dev_conf["ID"]."@".$_SERVER["SERVER_NAME"];
	// add needed js scripts to the page headers
	$eis_realtime_header=$eis_realtime_header.'
    <script src="../lib/RGraph/libraries/RGraph.common.core.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.common.dynamic.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.gauge.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.thermometer.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.vprogress.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.hprogress.js" ></script>
    <script src="../lib/RGraph/libraries/RGraph.scatter.js" ></script>
    <!--[if lt IE 9]><script src="../excanvas/excanvas.js"></script><![endif]-->
    <script src="../lib/RGraph/libraries/RGraph.common.effects.js"></script>
    <script src="../lib/jquery.min.js"></script>';
    // add the callbacks needed by the widgets
	if (eis_callback("signal")) {
	    eis_dev_call($thisdevice,"signal",$_REQUEST["signal"],array(),$outputpar);
	    die();
	}
	if (eis_callback("setvar")) {
		$set=explode("=",$_REQUEST["setvar"]);
	    eis_dev_call($thisdevice,"exec","setstatus",array($set[0]=>$set[1]),$outputpar);
	    die();
	}
}

// return a string containing the standard HTML header with some eis feature
// must be called after eis_realtime_widgets(), eis_realtime_handler() and before widget calls
function eis_page_header($title,$headers) {
	global $eis_conf,$eis_dev_conf,$eis_realtime_header,$eis_dev_status;
	$image="../lib/icons/generic.jpg";
	if (file_exists("../lib/icons/".$eis_dev_conf["class"].".jpg")) $image="../lib/icons/".$eis_dev_conf["class"].".jpg";
	if (file_exists("../lib/icons/".$eis_dev_conf["class"].".png")) $image="../lib/icons/".$eis_dev_conf["class"].".png";
	if (file_exists("picture.jpg")) $image="picture.jpg";
	if (file_exists("picture.png")) $image="picture.png";
	$img="<img align='middle' height=100 width=100 src='$image'> ";
	return
'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>'.$title.'</title>
    <link rel="stylesheet" type="text/css" href="../lib/eis.css">
    '.$eis_realtime_header.$headers.'
 </head>
<body>
 <a href="'.eis_dev_geturl("","").'">eis home</a> &nbsp&nbsp
 <a href="'.eis_dev_geturl($eis_dev_conf["ID"],"").'"> '.$eis_dev_conf["ID"].' home</a> &nbsp&nbsp
 <a href="'.eis_dev_geturl($eis_dev_conf["ID"],"").'/help.php"> '.$eis_dev_conf["ID"].' help</a>
 <h2>'.$img.$eis_dev_conf["ID"].' <i>('.$eis_dev_conf["class"].' at '.$_SERVER["SERVER_NAME"].', '.$eis_dev_status["configID"].' configuration)</i></h2>
 ';
}

// return a realtime widget containing the current simulation time
function eis_widget_timestamp($title) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_timestamp_$eis_realtime_counter";
	$out="<span id='$id'></span>\n";
	$out=$out."<script>
	function f_$id(v) {
    	document.getElementById('$id').innerHTML = '$title'+' &nbsp '+eis_date(v);
	}
	eis_widgets['f_$id']='timestamp';
	window['f_$id']('".$eis_dev_status["timestamp"]."');
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime widget for enable/disable signal management
function eis_widget_enable() {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	return eis_widget_led("enabled","red")." ".eis_widget_signal("enable","enable")." ".eis_widget_signal("disable","disable");
}

// return a realtime widget for power on/off signal management
function eis_widget_poweron() {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	return eis_widget_led("power","green")." ".eis_widget_signal("power on","poweron")." ".eis_widget_signal("power off","poweroff");
}

// return a realtime widget for cpower and cenergy levels
function eis_widget_cpower($title) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	// set widgets ids
	for($i=1;$i<4;$i++) {
		$pid[$i]="eis_cpower$i"."_".$eis_realtime_counter;
		$eid[$i]="eis_cenergy$i"."_".$eis_realtime_counter;
	}
	// output title if any
	if ($title=="") $out=""; else $out="<h4>$title</h4>\n";
	// output canvas table
	$out=$out."<table style='width:100%'><tr>\n";
	for($i=1;$i<4;$i++)
		$out=$out."<td style='text-align:center'><canvas id='".$pid[$i]."' width=230 height=230>[No canvas support]</canvas></td>\n";
	$out=$out."</tr><tr>\n";
	for($i=1;$i<4;$i++) 
		$out=$out."<td style='text-align:center'><div id='".$eid[$i]."'></div></td>\n";
	$out=$out."</tr></table>\n";
	// output gauges
 	$out=$out."<script>\n";
	for($i=1;$i<4;$i++) {
		// set widget parameters
	    if (array_key_exists("cpower$i",$eis_dev_conf)) $powermax=$eis_dev_conf["cpower$i"]; else $powermax=0;
	    if (array_key_exists("cpower$i",$eis_dev_status)) $powerval=$eis_dev_status["cpower$i"]; else $powerval=0;
	    if (array_key_exists("cenergy$i",$eis_dev_status)) $cenergy=$eis_dev_status["cenergy$i"]; else $cenergy=0;
	    $powerred=$powermax*0.7;
	    $powergreen=$powermax*0.3;
		$out=$out.
	    // draw gauges
	    "var ".$pid[$i]." = new RGraph.Gauge('".$pid[$i]."', 0, $powermax, $powerval);
        ".$pid[$i].".Set('chart.title','phase $i');
        ".$pid[$i].".Set('chart.red.start','$powerred');
        ".$pid[$i].".Set('chart.green.end','$powergreen');
        ".$pid[$i].".Set('chart.title.bottom', 'watt');
        ".$pid[$i].".Set('chart.green.color','green');
        ".$pid[$i].".Set('chart.red.color','red');
        RGraph.Effects.Gauge.Grow(".$pid[$i].");\n";
        // output cpower management functions
		$out=$out.
		"function f_".$pid[$i]."(v) {
        	".$pid[$i].".value=v;
        	RGraph.Effects.Gauge.Grow(".$pid[$i].");                    
		}
		eis_widgets['f_".$pid[$i]."']='cpower$i';\n";
        // output cenergy management functions
		$out=$out.
		"function f_".$eid[$i]."(v) {
			document.getElementById('".$eid[$i]."').innerHTML = '<h3><font color=red>'+v.toFixed(3)+' kWh</font></h3>';
		}
		eis_widgets['f_".$eid[$i]."']='cenergy$i';
		window['f_".$eid[$i]."']($cenergy);\n";
	}
	$out=$out."</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime widget for gpower and genergy levels
function eis_widget_gpower($title) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	// set widgets ids
	for($i=1;$i<4;$i++) {
		$pid[$i]="eis_gpower$i"."_".$eis_realtime_counter;
		$eid[$i]="eis_genergy$i"."_".$eis_realtime_counter;
	}
	// output title if any
	if ($title=="") $out=""; else $out="<h4>$title</h4>\n";
	// output canvas table
	$out=$out."<table style='width:100%'><tr>\n";
	for($i=1;$i<4;$i++)
		$out=$out."<td style='text-align:center'><canvas id='".$pid[$i]."' width=230 height=230>[No canvas support]</canvas></td>\n";
	$out=$out."</tr><tr>\n";
	for($i=1;$i<4;$i++) 
		$out=$out."<td style='text-align:center'><div id='".$eid[$i]."'></div></td>\n";
	$out=$out."</tr></table>\n";
	// output gauges
 	$out=$out."<script>\n";
	for($i=1;$i<4;$i++) {
		// set widget parameters
	    if (array_key_exists("gpower$i",$eis_dev_conf)) $powermax=$eis_dev_conf["gpower$i"]; else $powermax=0;
	    if (array_key_exists("gpower$i",$eis_dev_status)) $powerval=$eis_dev_status["gpower$i"]; else $powerval=0;
	    if (array_key_exists("genergy$i",$eis_dev_status)) $genergy=$eis_dev_status["genergy$i"]; else $genergy=0;
	    $powerred=$powermax*0.7;
	    $powergreen=$powermax*0.3;
		$out=$out.
	    // draw gauges
	    "var ".$pid[$i]." = new RGraph.Gauge('".$pid[$i]."', 0, $powermax, $powerval);
        ".$pid[$i].".Set('chart.title','phase $i');
        ".$pid[$i].".Set('chart.red.start','$powerred');
        ".$pid[$i].".Set('chart.green.end','$powergreen');
        ".$pid[$i].".Set('chart.title.bottom', 'watt');
        ".$pid[$i].".Set('chart.green.color','red');
        ".$pid[$i].".Set('chart.red.color','green');
        RGraph.Effects.Gauge.Grow(".$pid[$i].");\n";
        // output cpower management functions
		$out=$out.
		"function f_".$pid[$i]."(v) {
        	".$pid[$i].".value=v;
        	RGraph.Effects.Gauge.Grow(".$pid[$i].");                    
		}
		eis_widgets['f_".$pid[$i]."']='gpower$i';\n";
        // output cenergy management functions
		$out=$out.
		"function f_".$eid[$i]."(v) {
			document.getElementById('".$eid[$i]."').innerHTML = '<h3><font color=green>'+v.toFixed(3)+' kWh</font></h3>';
		}
		eis_widgets['f_".$eid[$i]."']='genergy$i';
		window['f_".$eid[$i]."']($genergy);\n";
	}
	$out=$out."</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime textual (<p>) widget for the status variable $statusvar with title $title
function eis_widget_text($title, $statusvar) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_".$statusvar."_".$eis_realtime_counter;
	// check options
	$args=2;
	if (func_num_args()<=$args) $options=array(); else $options=func_get_arg($args);
	if (array_key_exists("float",$options)) $v="v.toFixed(".$options["float"].")"; else $v="v";
	// output HTML text
	$out="<span id='$id'></span>\n";
	$out=$out."<script>
	function f_$id(v) {
    	document.getElementById('$id').innerHTML = '$title'+$v;
	}
	eis_widgets['f_$id']='$statusvar';
	window['f_$id']('".$eis_dev_status[$statusvar]."');
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime vertical bar widget for the status variable $statusvar with title $title
// the bar will have a scale from 0 to $max
function eis_widget_vbar($title,$statusvar,$max) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_".$statusvar."_".$eis_realtime_counter;
	// check options
	$args=3;
	if (func_num_args()<=$args) $options=array(); else $options=func_get_arg($args);
	if (array_key_exists("width",$options)) $width=$options["width"]; else $width=100;
	if (array_key_exists("height",$options)) $height=$options["height"]; else $height=400;
	// output canvas 
	$out="<canvas id='$id' width=$width height=$height>[No canvas support]</canvas>\n";
	// output bar
	$out=$out."<script>
	var $id = new RGraph.VProgress('$id',".$eis_dev_status[$statusvar].",$max);
  	$id.Set('title','".$title."');
  	$id.Set('colors',['Gradient(#eef:red)','Gradient(white:green)']);
  	$id.Set('labels.count',5);
  	$id.Set('scale.decimals',0);
  	$id.Set('gutter.right',50);
  	RGraph.Effects.VProgress.Grow($id);\n";
  	// output controlling function
	$out=$out."
	function f_$id(v) {
        $id.value=v;
        RGraph.Effects.VProgress.Grow($id);                    
	}
	eis_widgets['f_$id']='$statusvar';
	window['f_$id'](".$eis_dev_status[$statusvar].");
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime horizontal bar widget for the status variable $statusvar with title $title
// the bar will have a scale from 0 to $max
function eis_widget_hbar($title,$statusvar,$max) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_".$statusvar."_".$eis_realtime_counter;
	// check options
	$args=3;
	if (func_num_args()<=$args) $options=array(); else $options=func_get_arg($args);
	if (array_key_exists("width",$options)) $width=$options["width"]; else $width=500;
	if (array_key_exists("height",$options)) $height=$options["height"]; else $height=70;
	// output canvas 
	$out="<canvas id='$id' width=$width height=$height>[No canvas support]</canvas>\n";
	// output bar
	$out=$out."<script>
	var $id = new RGraph.HProgress('$id',".$eis_dev_status[$statusvar].",$max);
  	$id.Set('title','".$title."');
  	$id.Set('colors',['Gradient(#eef:red)','Gradient(white:green)']);
  	$id.Set('labels.count',5);
  	$id.Set('scale.decimals',0);
  	RGraph.Effects.HProgress.Grow($id);\n";
  	// output controlling function
	$out=$out."
	function f_$id(v) {
        $id.value=v;
        RGraph.Effects.HProgress.Grow($id);                    
	}
	eis_widgets['f_$id']='$statusvar';
	window['f_$id'](".$eis_dev_status[$statusvar].");
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime line plot widget for the status variable $statusvar with title $title
// the plot will have a Y scale from $min to $max
function eis_widget_plot($title,$statusvar,$min,$max) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_plot_".$eis_realtime_counter;
	// check options
	$args=4;
	if (func_num_args()<=$args) $options=array(); else $options=func_get_arg($args);
	if (array_key_exists("width",$options)) $width=$options["width"]; else $width=700;
	if (array_key_exists("height",$options)) $height=$options["height"]; else $height=400;
	// compute x labels
	$start=$eis_dev_status['sim_startime'];
	$end=$eis_dev_status['sim_endtime'];
	$hours=4;
	$nlabels=intval(($end-$start-3600*$hours)/(3600*$hours));
	$h=date("G",$start)+$hours/2;
	$labels="['$h'";
	for($i=1;$i<=$nlabels;$i++) $labels=$labels.",'".($h+$i*$hours)."'";
	$labels=$labels."]";
	// output canvas 
	$out="<canvas id='$id' width=$width height=$height>[No canvas support]</canvas>\n";
	// output global vars
	$out=$out."<script>
	var o_$id=".$eis_dev_status[$statusvar].";
	var d_$id=new Array([".$eis_dev_status["timestamp"].",o_$id]);\n";
  	// output controlling functions
  	// plot is recreated each time for updating (RGraph requirement)
  	// second function is needed for asyncronous update
	$out=$out."
	function f_$id(v) {
		if ('$statusvar' in idata) o_$id=idata['$statusvar'];
		var t=parseInt(v,10);
		d_$id.push([t,o_$id]);
		RGraph.Reset(document.getElementById('$id'));
		var plot = new RGraph.Scatter('$id',d_$id);
	  	plot.Set('chart.title','$title');
		plot.Set('chart.xmin',$start);
		plot.Set('chart.xmax',$end);
		plot.Set('chart.title.xaxis','hours');
		plot.Set('chart.labels',$labels);
		plot.Set('chart.ymin',$min);
		plot.Set('chart.ymax',$max);
		plot.Set('chart.gutter.left',100);
		plot.Set('chart.gutter.bottom',100);
	  	plot.Set('chart.tickmarks',null);
 	 	plot.Set('chart.line',true);
 	 	plot.Set('chart.line.colors',['red']);
 	 	plot.Set('chart.line.linewidth',2);
   		plot.Draw();
	}
	function f2_$id(v) {
		o_$id=v;
	}
	eis_widgets['f_$id']='timestamp';
	eis_widgets['f2_$id']='$statusvar';
	window['f_$id'](".$eis_dev_status["timestamp"].");
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime widget for setting a numerical status var value
function eis_widget_setvar($variable,$minvalue,$maxvalue) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_setvar_$eis_realtime_counter";
	$out="<b>Set <i>$variable</i> value ($minvalue,$maxvalue):</b> <input type='text' id='$id' size=16 onkeypress=\"f_$id();\">\n";
	$out=$out."<script>
	function f_$id() {
	    var key=window.event.keyCode;
	    if (key==13) {
	    	var value=document.getElementById('$id').value;
	    	if (value<$minvalue) value=$minvalue;
	    	if (value>$maxvalue) value=$maxvalue;
	    	eis_callback('setvar','$variable='+value);
	    	document.getElementById('$id').value='';
	    }
	}
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime led widget connected to a binary status variable
function eis_widget_led($variable,$color) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_led_$eis_realtime_counter";
	$out="<img id='$id' align='middle' height=25 width=25>\n";
	$out=$out."<script>
	function f_$id(v) {
		var s;
    	if (v) s='../lib/images/led_".$color."_on.png'; else s='../lib/images/led_off.jpg'; 
        document.getElementById('$id').src = s;
	}
	eis_widgets['f_$id']='$variable';
	window['f_$id']('".$eis_dev_status[$variable]."');
</script>\n";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime widget for sending a signal
function eis_widget_signal($label,$signal) {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_signal_$eis_realtime_counter";
	$out="<input type='button' value='$label' onClick=\"eis_callback('signal','$signal');\" />";
	$eis_realtime_counter++;
	return $out;
}

// return a realtime textual (<span>) widget for monitoring the blackout status
function eis_widget_blackout() {
	global $eis_dev_status,$eis_dev_conf,$eis_realtime_counter;
	$id="eis_blackout_".$eis_realtime_counter;
	if (array_key_exists("cline",$eis_dev_status) and $eis_dev_status["cline"]!="-----")
		$line=$eis_dev_status["cline"]." cline:";
	else
		$line=$eis_dev_status["gline"]." gline:";
	$out="<span id='$id'></span>\n";
	$out=$out."<script>
	function f_$id(v) {
		if (v) 
    		document.getElementById('$id').innerHTML = '<b>$line <font color=\"red\">blackout</font></b>';
    	else
    		document.getElementById('$id').innerHTML = '<b>$line OK</b>';
	}
	eis_widgets['f_$id']='blackout';
	window['f_$id']('".$eis_dev_status["blackout"]."');
</script>\n";
	$eis_realtime_counter++;
	return $out;
}


// return a string containing $space HTML spaces
function eis_spaces($spaces) {
	if ($spaces<0) $spaces=0;
	$sp="";
	for($i=0;$i<$spaces;$i++) $sp=$sp."&nbsp ";
	return $sp;
}

// function for printing a table with its title, headers and fields
// $header is an array of header strings
// $rows is an array of table row, each is an array of fields
// first column is left aligned, the others are centered 
function eis_print_datatable($title,$headers,$rows,$options) {
	// manage options
	if (!is_array($options)) $options=array();

	
	// print table
	if ($title!="") print "<br><b>$title</b><br>\n";
	print "	<table border=1><tr>";
	foreach ($headers as $f) print "<th>&nbsp $f &nbsp</th>";
	print "</tr>\n";
	foreach ($rows as $k=>$row) {
		print "<tr id='$k'>";
		foreach ($row as $j=>$f) {
			if ($j==0) $a="left"; else $a="center";
			if (array_key_exists($headers[$j],$options)) $a=$options[$headers[$j]];
			print "<td style='text-align:$a'>&nbsp $f &nbsp</td>";
		}
		print "</tr>\n";
	}
	print "</table>\n";
}


//////// other here




?>
