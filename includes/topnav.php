<?php

/*
************************************************************************
    This file is part of TSM Monitor.

    TSM Monitor is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    TSM Monitor is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TSM Monitor.  If not, see <http://www.gnu.org/licenses/>.
************************************************************************
*/

?>

<form action="<?php echo $_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']; ?>" method="post">
<?php 

if ($GETVars['qq'] != "admin" && !$_POST["edit"] == "edit") {
	if ($GETVars['qq'] != "index" && $GETVars['qq'] != "overview") {  
		echo "<input type='button' value='Pop Out' onclick='printpreview()'>";
		echo "<input type='submit' name='Edit' value='Edit' onclick='submit();'>"; 
		echo "<input type='submit' name='Refresh' value='Refresh' onclick='submit();'>"; 
	}

	echo "<select name='s' size=1 onChange='submit();'>";

	while(list($servername,$serveritems) = each($configarray["serverlist"])) {
		echo '<option value="'.$servername.'"';
		if ($GETVars['server'] == $servername){echo "SELECTED";}
		echo '> '.$servername.' ('.$serveritems["description"].')</option>';
	}
	echo "</select>";
}
?>
</form>
