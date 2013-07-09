<?php
// **** EIS ****
// eis master simulation page
// upf, Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");
include("private/master_lib.php");

// current page url
$page=eis_page_url();

// get parameters
if (isset($_REQUEST["action"])) $action= $_REQUEST["action"]; else $action="";
if (isset($_REQUEST["sim_id"])) $sim_id= $_REQUEST["sim_id"]; else $sim_id="";

// delete action
if ($action=="delete") {
	if (!eis_master_deletesim($sim_id)) die("cannot delete simulation: $eis_error -- $eis_errmsg");
}

// restart action
if ($action=="restart") {
	die('<script type="text/javascript">window.location.href="run.php?sim_id='.$sim_id.'&init";</script>');
}

// analyse action
if ($action=="analyse") {
	die("This action is not implemente yet !");
}


// print page headers
print eis_page_header("eis master","",null);

// simulation data table
$headers=array("ID","Name","Type","StartHour","Step (min)","Meteo Data","Price Data","Actions");
$rows=array();
$query="SELECT * FROM ".$eis_dev_conf["tablepfx"]."_simulations ORDER BY timestamp ASC";
if (!($result=$eis_mysqli->query($query))) die("master:cannotLoadSimulation: ".$eis_mysqli->error);
while ($row=$result->fetch_array(MYSQLI_ASSOC)) {
	$rows[]=array($row["simulID"],$row["name"],$row["type"],$row["starthour"],$row["step"],$row["meteo"],$row["price"],
		"<a href='$page?sim_id=".$row["simulID"]."&action=analyse'>analyse</a>, 
		 <a href='$page?sim_id=".$row["simulID"]."&action=restart'>restart</a>, 
		 <a href='#' onclick=\"sim_delete('".$row["simulID"]."')\">delete</a>");
}
eis_print_datatable("Available simulations:",$headers,$rows,null);

print "<br><button onClick='window.location.href=\"create.php?scan=rescan\"'>Create a new simulation</button>\n";


?>

<script>
	// confirm dialog for delete
	function sim_delete(id) {
		var r=window.confirm("Do you want to delete simulation "+id+" ?  (all data will be lost)");
		if (r==true)
			window.location.href='<?php print $page;?>?sim_id='+id+'&action=delete';
	}
</script>

</body>
</html>
