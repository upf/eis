###eis didactic SmartHome electrical simulator###

<b>Description:</b><br>
This scripts implements a simple electrical simulator of a SmartHome, where loads (cookers,washing machines, etc),
generators (PV, wind, etc.) and electrical storages (ups, etc.) can be present in a 3-phase or 1-phase configuration.
The simulator is written in PHP without object-oriented programming since the students had only a basic knowledge of C.
<br>
It is based on HTTP and REST approach using JSON associative array. Also an advanced interface is available using
an interactive approach similar to the BOSH technology (PHP, UDP client-server, Javascript, HTML) in order to have
the HTML interface changed in realtime when an event happens. RGraph/jquery is used for widgets.

<b>Tested OS's:</b><br>
MacOSX Mountain Lion, Ubuntu Linux 12.04<br>

<b>Status:</b><br>
The simulator is in development, working devices can be written and tested.<br>

<b>Requirements:</b><br>
Apache2, PHP 5.2.x, mySQL<br>

<b>Installation:</b><br>
1) Clone the entire github directory, usually a github folder in your home directory:<br>
	<i>MacOSX  -->  /Users/<yourname>/github/eis</i><br>
	<i>Linux   -->  /home/<yourname>/github/eis</i><br>
2) make a copy as follows:<br>
	<i>MacOSX  -->  sudo cp /Users/<yourname>/github/eis/system/eis_conf.php /etc/eis_conf.php</i><br>
	<i>Linux   -->  sudo cp /home/<yourname>/github/eis/system/eis_conf.php /etc/eis_conf.ph</i><br>
3) check Apache2, set FollowsSymLinks and AllowOverride in the web root directory<br>
4) make a symbolic link as follows:<br>
	<i>MacOSX  -->  sudo ln -s /Users/<yourname>/github/eis/devices /Library/WebServer/Documents/eis</i><br>
	<i>Linux   -->  sudo ln -s /home/<yourname>/github/eis/devices /var/www/eis</i><br>
5) create a mySQL database named "eis", and import the file eis.sql in the "system" directory inside the eis folder<br>
6) edit the file /etc/eis_conf.php and fill the required fields (read comments).<br>

<b>System check:</b><br>
If installation was correct, check the system by visiting the page http://localhost/eis/eis.master.A01/console.php<br>
Select a deviceID and in the text field, write "init timestamp 1000000" and press the button.<br>
The first lines of output should be something like these:<br>
	calling http://localhost/eis/selected_deviceID exec init timestamp 1000000<br>
	call OK<br>
	return parameters:<br>
	Array<br>
	(<br>
	[ID] => eis.meteo.A01<br>
	.....<br>
Remember that to begin to use a device for the first time, the "init" command must be used before any other call or when
the indexes of the status array change (e.g. add, remove or rename a status variable).<br>

<b>More info:</b><br>
Visit the directory "doc" inside the eis folder.<br>

