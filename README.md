eis didactic SmartHome electrical simulator
-------------------------------------------

<b>Description:</b>
This scripts implements a simple electrical simulator of a SmartHome, where loads (cookers,washing machines, etc),
generators (PV, wind, etc.) and electrical storages (ups, etc.) can be present in a 3-phase or 1-phase configuration.
The simulator is written in PHP without object-oriented programming since the students had only a basic knowledge of C.
<br>
It is based on HTTP and REST approach using JSON associative array. Also an advanced interface is available using
an interactive approach similar to the BOSH technology (PHP, UDP client-server, Javascript, HTML) in order to have
the HTML interface changed in realtime when an event happens. RGraph/jquery is used for widgets.

Tested OS's:
------------
MacOSX Mountain Lion, Ubuntu Linux 12.04

Status:
-------
The simulator is in development, working devices can be written and tested.

Requirements:
-------------
Apache2, PHP 5.2.x, mySQL


Installation:
-------------
1) Clone the entire github directory, usually a github folder in your home directory:
	MacOSX  -->  /Users/<yourname>/github/eis
	Linux   -->  /home/<yourname>/github/eis
2) make a copy as follows:
	MacOSX  -->  sudo cp /Users/<yourname>/github/eis/system/eis_conf.php /etc/eis_conf.php
	Linux   -->  sudo cp /home/<yourname>/github/eis/system/eis_conf.php /etc/eis_conf.ph
3) check Apache2, set FollowsSymLinks and AllowOverride in the web root directory
4) make a symbolic link as follows:
	MacOSX  -->  sudo ln -s /Users/<yourname>/github/eis/devices /Library/WebServer/Documents/eis
	Linux   -->  sudo ln -s /home/<yourname>/github/eis/devices /var/www/eis
5) create a mySQL database named "eis", and import the file eis.sql in the "system" directory inside the eis folder
6) edit the file /etc/eis_conf.php and fill the required fields (read comments).

System check:
-----------------
If installation was correct, check the system by visiting the page http://localhost/eis/eis.master.A01/console.php
Select a deviceID and in the text field, write "init timestamp 1000000" and press the button.
The first lines of output should be something like these:
	calling http://localhost/eis/selected_deviceID exec init timestamp 1000000
	call OK
	return parameters:
	Array
	(
	[ID] => eis.meteo.A01
	.....
Remember that to begin to use a device for the first time, the "init" command must be used before any other call or when
the indexes of the status array change (e.g. add, remove or rename a status variable).

More info:
----------
Visit the directory "doc" inside the eis folder.

