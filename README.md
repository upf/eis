###eis didactic SmartHome electrical simulator###

<b>Description:</b><br>
This scripts implements a simple electrical simulator of a SmartHome, where loads (cookers,washing machines, etc),
generators (PV, wind, etc.) and electrical storages (ups, etc.) can be present in a 3-phase or 1-phase configuration.
The simulator is written in PHP without object-oriented programming since the students had only a basic knowledge of C.
This simulator is also a practical demonstrator of several computer tecnologies introduced in the course (*nix,HTTP,Ajax, etc.)
<br>
It is based on HTTP and REST approach using JSON associative array. Also an advanced interface is available using
an interactive approach similar to the BOSH technology (PHP, UDP client-server, Javascript, HTML) in order to have
the HTML interface changed in realtime when an event happens. RGraph/jquery is used for widgets.

<b>Tested OS's:</b><br>
MacOSX Mountain Lion, Ubuntu Linux 12.04<br>

<b>Status:</b><br>
The simulator is in development, working devices can be written and tested.<br>

<b>Requirements:</b><br>
Working on Apache2, PHP 5.2.x, myql 5.5.27<br>
On MacosX Apache2 and PHP are already present but must be enabled and configured, while mysql must be installed.

<b>First time installation:</b><br>
1) Clone the entire github directory, usually a github folder in your home directory:<br>
	<i>MacOSX  -->  /Users/...yourname.../github/eis</i><br>
	<i>Linux   -->  /home/...yourname.../github/eis</i><br>
2) goto the eis bin directory and run the installer as root<br>
	<i>MacOSX  -->  cd /Users/...yourname.../github/eis/bin</i><br>
	<i>Linux   -->  cd /home/...yourname.../github/eis/bin</i><br>
	<i>sudo ./install  your_root_mysql_username  your_root_mysql_password</i><br>
3) follow installer instructions<br>
4) when successfully installed, visit http://...your_ip.../eis with a browser

<b>Update installation:</b><br>
1) Clone the entire github directory in the same directory you used the first time installation:<br>
2) visit http://<your_ip>/eis with a browser and follow instructions (run installer again if requested)

<b>System check:</b><br>
If installation was correct, check the system by visiting the page http://...your_ip.../eis<br>
Using the console write: "eis.master exec ping test 55" and press Enter.<br>
The first lines of output should be something like these:<br>
	<i>call OK:<br>
Array<br>
(<br>
    [test] => 55<br>
)<br>

<b>More info:</b><br>
Visit the directory "doc" inside the eis folder.<br>

