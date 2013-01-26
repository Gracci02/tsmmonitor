<?php

/*
************************************************************************
    TSM Monitor v1.0 (www.tsm-monitor.org)

    Copyright (C) 2009 Michael Clemens <mail@tsm-monitor.org>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
************************************************************************
*/

include_once "includes/functions.php";

initialize();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">
<script type="text/javascript">
<!--
function printpreview() {
window.open( "<?php echo $printURL ?>", "myWindow", "status = 1, fullscreen=yes,scrollbars=yes" )
}
//-->
</script>

<title>TSM Monitor</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
<div id="inhalt">
<table cellspacing="4" cellpadding="2" border="0" id="design">
<?php if (!$GETVars['print']) { ?>
<tr>
    <td colspan="2" id="head"><a class='navheader' href="index.php"><img src="images/PollDTitle.png" border=0></img></a></td>
</tr>
<?php } ?>
<tr>
<?php if ($bLoggedIn && !$GETVars['print']) { ?>
    <td id="tnleft" width="160"></td>
    <td id="tnright"width="740" height="30" align="right"><?php if ($bLoggedIn) { include_once "includes/topnav.php"; }  ?></td>
<?php } ?>
<?php if ($bLoggedIn && $GETVars['print']) { ?>
    <td id="printpreviewheader" width="100%" height="30" align="center"><?php echo $queryarray[$GETVars['qq']]["label"];  ?></td>
<?php } ?>
</tr>
<tr>
<?php if ($bLoggedIn && !$GETVars['print']) { ?>
<!-- Start left cik navigation menu -->
    <td id="menue">
        <div class="menuelinks">
		<?php echo get_menu( $submenu, 'index.php?'.$menukey."&s=".$GETVars['server'] );  ?>
        </div>
	<br>
        <div class="menuelinks">
		<?php echo get_menu( $adminmenu, "index.php?q=admin&m=".$GETVars['menu']."&s=".$GETVars['server'] );  ?>
        </div>
	<br>
        <div class="menuelinks">
		<?php echo get_info();  ?>
        </div>
        <img src="/images/trans.gif" alt="" width="150" height="1" border="0"><br>
    </td>
<!-- End left cik navigation menu -->
<?php } ?>
    <td id="content">
<?php

// main content, right of menu
if (isset($_SESSION["logindata"]["user"]) && isset($_SESSION["logindata"]["pass"]) && $GETVars['qq'] != "logout" && $bLoginOK){
	if ($GETVars['qq'] != "" && $GETVars['qq'] != "overview"){

		// show overview page
		if ($GETVars['qq'] == "index") {
			include_once "includes/overview.php" ;
			echo "<p align='right'>(Values marked with '*' are cached results)</p>";
		
		// show admin page
		} else if ( $GETVars['qq'] == "admin" ) {
			echo "nothing to see here";

		// show serverlist
		} else if ( $GETVars['qq'] == "serverlist" ) {
			echo getServerlist();

		// show graphical chart
		} else if ( strstr($GETVars['qq'], 'timetable'))  {
			if ($_POST["edit"] == "edit") {
				echo showEdit();
			}else {
				$timesteps = array("1 hour" => "1", "6 hours" => "6", "12 hours" => "12", "24 hours" => "24");
				echo "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server']." method='post'>";
				if ($queryarray[$GETVars['qq']]["searchfield"] != "") {
					// get value from combobox
					if ($_POST["timestep"] != "") {
						$_SESSION['selectedtimestep'] = $_POST["timestep"];
					}
					echo "<table width='100%'><tr><td align='center'>";
					echo "<input type='submit' name='back' value='<-' onclick='submit();'>";
					echo "<select name='timestep' size=1 onChange='submit();'>";
					// build combobox
					while(list($label,$value) = each($timesteps)) {
						echo '<option value="'.$value.'"';
						if ($_SESSION['selectedtimestep'] == $value){echo "SELECTED";}
						echo '> '.$label.'</option>';
					}
					echo "</select>";
					echo "<input type='submit' name='forward' value='->' onclick='submit();'>";
					echo "</td></tr></table>";
					if ($_POST["back"] != "") {
						$_SESSION['timeshift'] += $_SESSION['selectedtimestep'];
					}
					if ($_POST["forward"] != "") {
						$_SESSION['timeshift'] -= $_SESSION['selectedtimestep'];
					}
					if ($_SESSION['timeshift'] < 0) {
						$_SESSION['timeshift'] = 0;
					}
				}
				$searchfield = 24 + $_SESSION['timeshift'];
				$tablearray = execute($query, 'no', 'timetable');	
				$headerarray = $queryarray[$GETVars['qq']]["header"]["column"];
				echo generate_timetable($tablearray, $headerarray[0]);
				echo "</form>";
			}
		// "vertical" table
		} else if ( strstr($GETVars['qq'], 'vertical'))  {
			if ($_POST["edit"] == "edit") {
				echo showEdit();
			}else {
				$i = 0;
				$headerarray = $queryarray[$GETVars['qq']]["header"]["column"];
				$tablearray = execute($query, 'no', 'verticaltable');
				echo "<table class='zebra'>";
				for ( $co = 0; $co <= count($headerarray); $co++) {
					if ($i == 0) {
						echo "<tr class='d0'>";
						$i = 1;
					} else {
						echo "<tr class='d1'>";
						$i = 0;
					}
					echo "<td><b>".$headerarray[$co]['label']."</b></td><td>".$tablearray[$co]."</td></tr>";
				}
				
				echo "</table>";
			}
		// show normal table layout
		} else {
			if ($_POST["Edit"] == "Edit") {
				echo showEdit();
			}else {
				if ($_POST["Save"] == "Save") {
					saveQuery();
				}
				if ($_POST["Refresh"] == "Refresh") {
					$_SESSION["cachedqueries"][$GETVars['server']][$query]["timestamp"] = '';
				}
				$cache = $queryarray[$GETVars['qq']]["cache"];
				$searchfieldxml = $queryarray[$GETVars['qq']]["searchfield"];
				if (isset($searchfieldxml) && $searchfieldxml != "") {
					echo getSearchfield();
				}
				$searchfield = $_POST["searchfield"];
				if ($searchfield == "" && $_GET['sf']!=""){
					$searchfield = $_GET['sf'];
				}
				if ( $searchfieldxml == "" || $searchfield != "") {
					echo "<table class='zebra'>";
					echo get_tableheader($queryarray[$GETVars['qq']]["header"]["column"]);
					echo execute($query, 'no', 'table', $cache);
					echo "</table>";
				}
			}

		}
	}
} else {
	if (!$bLoginOK){
		$errormsg = "Login failed!";
	}else{
		$errormsg = "Login";
	}

	session_unset();
	$_SESSION=array();
	include_once "includes/login.php";

}
$_SESSION['from'] = $GETVars['qq'];
session_write_close(void); 
?>

</tr>
<tr>
    <td colspan="2" id="footer">TSM Monitor v1.0 Copyright &copy; 2008-2009 M. Clemens (<a class='nav' href="http://www.tsm-monitor.org">www.tsm-monitor.org</a>)</td>
</tr>
</table>

</div>
</body>
</html>
