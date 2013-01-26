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


//**************************
//***** initialize *********
//**************************

function initialize() {

global $configarray;
global $queryarray;
global $GETVars;

global $bLoggedIn;
global $bLoginOK;
global $from;
global $adminmenu;
global $submenu;
global $menukey;
global $printURL;
global $query;

$bCheckLogin;

session_name("tsmmonitor");
session_start();

if (!isset($_SESSION) || !isset($_SESSION['configarray'])) {
        $_SESSION['configarray']= getConfigArray();
}

// Login if not logged in and credentials are ok
if ((isset($_POST) && isset($_POST["loginname"]) && isset($_POST["loginpasswort"]) && (!isset($_SESSION["logindata"])))){
        $_SESSION["logindata"]["user"] = $_POST["loginname"];
        $_SESSION["logindata"]["pass"] = $_POST["loginpasswort"];
        $_SESSION["logindata"]["loggedin"] = TRUE;
        $bCheckLogin = TRUE;
}

// GET-variables
$GETVars["menu"] = $_GET['m'];
$GETVars["qq"] = $_GET['q'];
$GETVars['ob'] = $_GET['sort'];
$GETVars['print'] = $_GET['print'];
$GETVars['orderdir'] = $_GET['so'];

if ($_POST['s'] != ''){
        $GETVars['server'] = $_POST['s'];
} else {
        $GETVars['server'] = $_GET['s'];
}


// Session-variables
$from = $_SESSION['from'];
$configarray = $_SESSION['configarray'];


// get configarray items
$adminmenu = $configarray["adminmenu"];
$timeout = $configarray["timeout"];

// timeout
if( !ini_get('safe_mode') && ini_get('max_execution_time')!=$timeout){
        ini_set('max_execution_time', $timeout);
}

// set defaults if vars are empty
if ($GETVars["menu"] == "") { $GETVars["menu"]="main"; }
if ($GETVars["qq"] == ""){ $GETVars["qq"]="index"; }
if ($GETVars['server'] == "") { $GETVars['server']=$configarray["defaultserver"]; }
if ($GETVars['orderdir'] == "") { $GETVars['orderdir'] = "asc"; }
if ($GETVars['print'] == "") { $GETVars['print'] = FALSE; } else if ($GETVars['print'] == "true") { $GETVars['print'] = TRUE; }

// Check if logged in
if ($GETVars["qq"] != "logout" && isset($_SESSION["logindata"]["loggedin"])) {
        $bLoggedIn = $_SESSION["logindata"]["loggedin"];
}
if ($_SESSION['timeshift'] == '' ||  !strstr($GETVars["qq"], 'dynamictimetable')){
        $_SESSION['timeshift'] = 0 ;
}

$submenu = $configarray["menuarray"][$GETVars['menu']];
$menukey = "q=".$GETVars['qq']."&m=".$GETVars["menu"];

$query = $configarray["queryarray"][$GETVars['qq']]["query"];
$orderby = $configarray["queryarray"][$GETVars['qq']]["orderby"];

if (isset($GETVars['ob']) && $GETVars['ob']!=""){
        $query = $query." order by ".$GETVars['ob']." ".$GETVars['orderdir'];
} else if (isset($orderby) && $orderby!="") {
        $query = $query." order by ".$orderby." ".$GETVars['orderdir'];
}

$bLoginOK = TRUE;
if ($bCheckLogin){
        $bLoginOK = checkLogin();
        $bLoggedIn = $bLoginOK;
}
$printURL = "index.php?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server']."&print=TRUE";
$queryarray = $configarray["queryarray"];

}



//********************
//***** menu *********
//********************

function get_menu($menu = '', $activelink = '') {

	global $GETVars;
	global $configarray;

	if (!isset($menu)) { return ""; };
	while(list($key, $val) = each($menu)) {

		$bCont = TRUE;
		if (strstr($val, '_notforlibclient')){
			if ($configarray["serverlist"][$GETVars['server']]["libraryclient"] == "yes"){
				$bCont = FALSE;
			}
			//$val=preg_replace('/[_notforlibclient]/', '', $val);
			$val=str_replace("_notforlibclient","",$val);
			
		}
		$key = "index.php?".$key."&s=".$GETVars['server'];

		if ($val == "trennlinie") {
			$links .= "<br>\n";
		} else if ($bCont){

			if ($key != $activelink && $key != $activelink) {
				$links .= "<a href=\"$key\">$val</a>\n";
			} else {
				$links .= "<div class=\"aktuell\">$val</div>\n";
			}
		}

	}
	return $links;
}

//********************
//***** info *********
//********************

function get_info() {

	global $configarray;
	global $GETVars;
	global $query;
	$ret = "";

	$label = $configarray["queryarray"][$GETVars['qq']]["label"];
	$info = $configarray["queryarray"][$GETVars['qq']]["info"];

	if ($info != ""){
		$ret .= "<div class='sidebarinfo'><b>".$label.":</b><br><br>".$info;
		if (isset($_SESSION["cachedqueries"][$GETVars['server']][$query]) && $_SESSION["cachedqueries"][$GETVars['server']][$query]["timestamp"] != '' ) {
			$ret .= "<br><br><br><i>Results shown on this page have been read from cache (Query time: ".strftime("%H:%M:%S", $_SESSION["cachedqueries"][$GETVars['server']][$query]["timestamp"]).")</i>";
		}
	$ret .= "</div>";
	return $ret;
	}
}


//********************
//*** table header ***
//********************

function get_tableheader($headerarray = '') {

	$tableheader="<tr>";
	global $GETVars;
	global $orderby;
	global $searchfield;
	$orderdir = $GETVars['orderdir'];

	if ($orderdir == "asc"){
		$sonew="desc";
	} else if ($orderdir == "desc") {
		$sonew="asc";
	}

	// If table has more than one column
	if (is_array($headerarray) && is_array($headerarray[0])) {
		foreach ($headerarray as $col) {
			$label = $col["label"];
			$name = $col["name"];
			$arrow = "";
			if (($GETVars['ob'] == $name && $GETVars['ob']!="") || ($GETVars['ob']=="" && $orderby!="" && $orderby == $name)){
				$link = "href='index.php?q=".$GETVars['qq']."&m=".$GETVars['menu']."&sort=".$name."&sf=".$searchfield."&so=".$sonew."&s=".$GETVars['server']."'";
				if ($orderdir == "asc") {
					$arrow = "&uArr;";
				} else if ($orderdir == "desc") {
					$arrow = "&dArr;";
				}
			} else {
				$arrow = "";
				$link = "href='index.php?q=".$GETVars['qq']."&m=".$GETVars['menu']."&sort=".$name."&sf=".$searchfield."&s=".$GETVars['server']."'";
			}
			$tableheader = $tableheader."<th><a class='navhead' ".$link.">".$label." ".$arrow."</a></th>";
		}
	} else {
		if ($orderdir == "asc") {
			$arrow = "&uArr;";
		} else if ($orderdir == "desc") {
			$arrow = "&dArr;";
		}
		$link = "href='index.php?q=".$GETVars['qq']."&m=".$GETVars['menu']."&sort=".$name."&sf=".$searchfield."&so=".$sonew."'";
		$label = $headerarray["label"];
		$tableheader = $tableheader."<th><a class='navhead' ".$link.">".$label." ".$arrow."</a></th>";
	}
	$tableheader=$tableheader."</tr>";


	return $tableheader;
}

//***************************
//*****  check login  *******
//***************************

function checkLogin() {

	global $configarray;
	global $GETVars;
	$user = $_SESSION["logindata"]["user"];
	$pass = $_SESSION["logindata"]["pass"];
	$server = $GETVars['server'];
	$ip = $configarray["serverlist"][$server]["ip"];
	$port = $configarray["serverlist"][$server]["port"];
	$ret = TRUE;
	$mustnotbenull="";

	$handle = popen("dsmadmc -se=$server -id=$user -password=$pass -TCPServeraddress=$ip -COMMMethod=TCPIP -TCPPort=$port", 'r');
	if ($handle) {
	    while (!feof($handle)) {
		$read = fgets($handle, 4096);
		$mustnotbenull .= $read;
		if (strstr($read, 'ANS8034E') || strstr($read, 'ANS8023E')) {
			$ret = FALSE;
		}
	    }

	} else {
		echo "no handle!";
	}
	pclose($handle);

	if ($mustnotbenull == "") {
		echo "<b>Cannot execute/find dsmadmc. Check if it's in PATH and/or that permissions have been set correctly (Linux/Unix: chmod ugo+x dsmadmc)!</b>";
		$ret = FALSE;
	}

	return $ret;

}


//***************************
//*****  check for alert ****
//***************************

function checkAlert($comperator = '', $alertval = '', $val = '') {

	$error = false;
	
	if (substr($val, -1) == "*") {
		$val = substr($val,0,-1);
	}
	if ($comperator == "equal"){
		if ($val == $alertval ){
			$error=true;
		}
	} else if ($comperator == "notequal"){
		if ($val != $alertval ){
			$error=true;
		}
	} else if ($comperator == "less") {
		if ($val < $alertval ){
			$error=true;
		}
	} else if ($comperator == "more") {
		if ($val > $alertval ){
			$error=true;
		}
	}
	return $error;

}


//***************************
//*****  execute  ***********
//***************************

function execute($query = '', $overview = 'no', $type = 'table', $cache = 'no') {

	global $configarray;
	global $GETVars;
	$server = $GETVars['server'];
	$user = $_SESSION["logindata"]["user"];
	$pass = $_SESSION["logindata"]["pass"];
	$ip = $configarray["serverlist"][$server]["ip"];
	$port = $configarray["serverlist"][$server]["port"];
	global $searchfield;
	//global $queryarray;
	
	$colorsarray = $configarray["colorsarray"];
	global $orderdir;

	$alerting = $configarray["queryarray"][$GETVars['qq']]["alerting"];

	$outp = '';
	$outp_cache = '';
	$stop=FALSE;
	$tablearray = array(); 

	$originalquery = $query;
	$query = ereg_replace("NOTEQUAL","<>",$query);
	$query = ereg_replace("LESS","<",$query);

	if (isset($searchfield) && $searchfield != ""){
		$query = ereg_replace("SEARCHFIELD","$searchfield",$query);
	}
	if ($cache=='yes' && isset($_SESSION["cachedqueries"]) && isset($_SESSION["cachedqueries"][$server][$originalquery]) && $_SESSION["cachedqueries"][$server][$originalquery]["timestamp"] != ''){
		$outp = $_SESSION["cachedqueries"][$server][$originalquery]["query"];
	} else {


		$handle = popen("dsmadmc -se=$server -id=$user -password=$pass -TCPServeraddress=$ip -COMMMethod=TCPIP -TCPPort=$port -dataonly=yes -TAB \"$query\" ", 'r');


		if ($handle) {
		    if ($type == "table") {
			    $i=1;
			    while (!feof($handle) && !$stop) {
				$read = fgets($handle, 4096);
				$stop = strstr($read, 'ANR2034E');
				if ($read != ' ' && $read != '' && !$stop) {
					$read=preg_replace('/[\n]+/', '', $read);
					if ($overview == 'no') {
						$color = "";
						$cols = split("\t", $read);
						$col = $cols[$alerting["alert_field"]];
						$error = checkAlert($alerting["alert_comp"], $alerting["alert_val"], $col);
						if($error) {
							$color = $alerting["alert_col"];
						} else {
							$color = $alerting["ok_col"];
						}
						$colorzebra = $colorsarray[$color][$i];
						if ($i % 2 == 0) {
							$outp = $outp."<tr class='d1'>";
						}else{
							$outp = $outp."<tr class='d0'>";
						}
						$i++;
						for ($co = 0; $co < count($cols); $co++) {
							if($color!="" && $alerting["alert_field"]==$co) {
								if ($i % 2 == 0) {
									$cellcol = $colorsarray[$color][1];
								} else {
									$cellcol = $colorsarray[$color][0];
								}
								$outp = $outp."<td bgcolor='".$cellcol."'>".$cols[$co]."</td>";
							} else {
								$outp = $outp."<td>".$cols[$co]."</td>";
							}
						}
						$outp = $outp."</tr>\n";
						$outp_cache = $outp;
					} else if ($overview == 'yes'){	
						$read=ereg_replace("\t"," - ",$read);
						$outp = $outp.$read;
						if ($cache == 'yes') { 
							$outp_cache = $outp."*"; 
						}
					}
				}
			    }
		    } 
		    else if ($type == "verticaltable") {

			    while (!feof($handle) && !$stop) {
				$read = fgets($handle, 4096);
				$stop = strstr($read, 'ANR2034E');
				if ($read != ' ' && $read != '' && !$stop) {
					$read = preg_replace('/[\n]+/', '', $read);
					$outp = split("\t",$read);
					$stop = true;
				}
			    }
		    }
		    else if ($type == "timetable") {
			    $outp = array();;
			    while (!feof($handle) && !$stop) {
				$read = fgets($handle, 4096);
				$stop = strstr($read, 'ANR2034E');
				if ($read != ' ' && $read != '' && !$stop) {
					$read=preg_replace('/[\n]+/', '', $read);
					$rowarray = explode("\t",$read);
					$rowarray2 = array();
					while(list($keycell, $valcell) = each($rowarray)) {
						if ($keycell == 0 || $keycell == 3) {
							$rowarray2[$keycell] = $valcell;
						} else {
							$date = $rowarray[$keycell];
							$rowarray2[$keycell] = mktime(substr($date,11,2),substr($date,14,2),substr($date,17,2),substr($date,5,2),substr($date,8,2),substr($date,0,4));
						}
					}
					array_push($outp, $rowarray2);
					}
				    }

			    }
			if ($cache == 'yes') {
				$_SESSION["cachedqueries"][$server][$originalquery]["query"] = $outp_cache;
				$_SESSION["cachedqueries"][$server][$originalquery]["timestamp"] = time();
			}
		}
		pclose($handle);
	}

	return $outp;
}

//*******************************
//*** generate server list    ***
//*******************************

function getSearchfield() {

	global $GETVars;
	global $searchfieldxml;
	$ret = "";

	$link = $_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server'];
	$ret .= "<form action=".$link." method='post'>";
	$ret .= "<b>".$searchfieldxml.": </b>";
	$ret .= "<input name='searchfield' type='text' size='30' maxlength='30' value='".$_POST["searchfield"]."'>  ";
	$ret .= "<input type='submit' name='Search' value='Search' onclick='submit();'>";
	$ret .= "</form><br>";

	return $ret;

}


//*******************************
//*** generate server list    ***
//*******************************

function getServerlist() {

	global $configarray;
	global $from;
	global $GETVars;
	$ret = "";
	$serverlist = $configarray["serverlist"];

	$i = 0;
	$ret = "<table class='zebra'>";
	$ret .= "<tr><th>Servername</th><th>Description</th><th>IP-Address</th><th>Port</th></tr>";
	while(list($servername,$serveritems) = each($serverlist)) {
		$listip = $serveritems["ip"];
		$listdescription = $serveritems["description"];
		$listport = $serveritems["port"];
		if ($i == 0) {
			$ret .= "<tr class='d0'>";
			$i = 1;
		} else {
			$ret .= "<tr class='d1'>";
			$i = 0;
		}
		$listlink = $_SERVER['PHP_SELF']."?q=".$from."&m=".$GETVars['menu']."&s=".$servername;
		$ret .= "<td><a class='nav' href='".$listlink."'>".$servername."</a></td><td>".$listdescription."</td><td>".$listip."</td><td>".$listport."</td></tr>";
	}

	return $ret."</table>";

}


//*******************************
//*** generate overview table ***
//*******************************

function get_overview_rows($subindexqueryarray = '') {

	global $GETVars;
	global $configarray;

	$out="";
	$i=0;

	while(list($key, $val) = each($subindexqueryarray)) {

		$bgcol="";
		$comperator = "";
		$alertval = "";
		$alertcol = "";
		$cellcolors = $configarray["colorsarray"];

		$cache = $subindexqueryarray[$key]["cache"];
		if ($configarray["serverlist"][$GETVars['server']]["libraryclient"] == "yes" && $subindexqueryarray[$key]["notforlibclient"] == "yes") {
			$res = "-";
		} else {
			$res = execute($subindexqueryarray[$key]["query"], 'yes', 'table', $cache);
		}
		
		if ($i == 1){
			$out = $out."<tr class='d1'><td width='50%'>";
			$i=0;
		}else{
			$out = $out."<tr class='d0'><td width='50%'>";
			$i=1;
		}
		$out .= $subindexqueryarray[$key]["header"];
		$comperator = $subindexqueryarray[$key]["alert_comp"];
		$alertval = $subindexqueryarray[$key]["alert_val"];
		$alertcol = $subindexqueryarray[$key]["alert_col"];
		$unit = $subindexqueryarray[$key]["unit"];
		$error = checkAlert($comperator, $alertval, $res);
		if ($error && $res != "-"){
			$bgcol="bgcolor='".$cellcolors[$alertcol][$i]."'";
		} else {
			$bgcol="bgcolor='".$cellcolors["green"][$i]."'";
		}
		$out .= "</td><td align='center' $bgcol>".$res." ".$unit."</td></tr>\n";
	}

	return $out;

}

//**********************************
//*** show edit page for queries ***
//**********************************

function showEdit(){

	global $configarray;
	global $GETVars;
	$out = "";

	$rowi=-1;
	$out .= "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']." method='post'>";
	$out .= "<table class='zebra'>";
	$out .= "<tr><th>Key</th><th>Value</th></tr>";
	while(list($key, $val) = each($configarray["queryarray"][$GETVars['qq']])) {
		$rowi++;
		$out .= "<tr class='d".$rowi."'><td>".ucfirst($key)."</td><td>";
		if (!is_array($val)) {
			if ($key == "query") {
				$out .= "<textarea name='".$key."'  cols='80' rows='10' >".$val."</textarea>";
			} else if ($key == "cache") {
				$selyes = "";
				$selno = "";
				if ($val == "yes") {
					$selyes = "SELECTED";
				} else {
					$selno = "SELECTED";
				}
				$out .= "<select name='cache' size='1'>";
				$out .= "<option value='yes' ".$selyes." >yes</option>";
				$out .= "<option value='no' ".$selno." >no</option>";
				$out .= "</select>";

			} else  {
				$out .= "<input name='".$key."' type='text' size='30' maxlength='30' value='".$val."'>";

			}
		} else {
			if ($key == "header"){
				$headerlabelstring = '';
				$headernamestring = '';
				if (is_array($val["column"])){
					while(list($keyheader, $valheader) = each($val["column"])) {
		
						if ($headerlabelstring == '') {
							$headerlabelstring .= $valheader["label"];
							$headernamestring .= $valheader["name"];
						} else {
							$headerlabelstring .= ",".$valheader["label"];
							$headernamestring .= ",".$valheader["name"];
						}
					}
				 
				} else {
					$headerlabelstring = $val["column"];

				}

			
			$out .= "<input name='header' type='text' size='100' maxlength='255' value='".$headerlabelstring."'>";
			//$out .= "<br>";
			//$out .= "<input name='headername' type='text' size='100' maxlength='255' value='".$headernamestring."'>";
			}
		}
		if ($rowi == 1) { $rowi = -1; }
		$out .= "</td></tr>\n";
	}
	$out .= "</table>";

	$out .= "<input type='submit' name='Save' value='Save' onclick='submit();'>";
	$out .= "<input type='submit' name='Cancel' value='Cancel' onclick='submit();'>";
	$out .= "</select>";

return $out;

}

//*************************
//*** save edited query ***
//*************************

function saveQuery(){

        global $configarray;
        global $GETVars;
        global $menukey;
	global $query;

	$queryarray = $configarray["queryarray"];
	

	while(list($key, $val) = each($queryarray[$GETVars['qq']])) {
		if (!is_array($val)) {
			$queryarray[$GETVars['qq']][$key] = stripslashes($_POST[$key]);
                } else {
			$header = array();
                        if ($key == "header"){
				$queryarray[$GETVars['qq']][$key]["column"] = split(",",$_POST[$key]);
			}
                }
	}
	$_SESSION["configarray"]["queryarray"][$GETVars['qq']] = $queryarray[$GETVars['qq']];
	$_SESSION["configarray"]["menuarray"][$GETVars["menu"]][$menukey] = $queryarray[$GETVars['qq']]["label"];
	$_SESSION["configarray"]["menuarray"][$GETVars["menu"]][$menukey] = $queryarray[$GETVars['qq']]["label"];
	$configarray["menuarray"][$GETVars['menu']][$menukey] = $queryarray[$GETVars['qq']]["label"];
	$query =  $queryarray[$GETVars['qq']]["query"];

	// TODO: Delete cached query in session


}

//*******************************
//*** header for timetable    ***
//*******************************

function generate_timetable_header($startpunkt = '', $FirstCol = '') {
	
	$header = $FirstCol["label"];
	$out= "<tr><th>".$header."</th><th background=images/tablebg.gif>";
	for ($count = 0; $count <= 24; $count++) {
		$imagename = strftime("%H", $startpunkt+($count*3600));
		$out .= "<img src='images/".$imagename.".gif' height=20px width=30px title='".strftime("%H:00 (%D)", $startpunkt+($count*$hour))."' />";
	}

	$out .= "</th></tr>";

return $out;

}

//*******************************
//*** generate timetable      ***
//*******************************

function generate_timetable($tablearray = '', $FirstCol = '') {

	global $searchfield;

	$now = time();
	$out = '';
	$height = 8;
	$faktor = 120;
	$oneday = 86400;
	$onehour = 3600;
	$tolerance = 1200;

	$startpunkt = ((ceil(time()/$onehour)*$onehour)-$onehour-$oneday)-(($searchfield-24)*$onehour);
	$endpunkt = $startpunkt + $oneday + $onehour;
	$lastpoint = ($endpunkt - $startpunkt)/$faktor;

	$out .= "<table class='timetable' width='".$lastpoint."'>";
	$out .= generate_timetable_header($startpunkt, $FirstCol);
	$out .= "</td></tr>";

	$lasttimepoint=$now-($searchfield*$onehour)-$tolerance;

	$repeatingcol = "";
	$ii=1;

	while(list($keyrow, $valrow) = each($tablearray)) {
		if ($valrow[1] <= $endpunkt && $valrow[2] > $lasttimepoint){
			$name = $valrow[0];
			$status = $valrow[3];
			$statusmsg = "";
			$dur = strftime("%H:%M", ($valrow[2]-$valrow[1])-$onehour);
			$shade="";
			if ($valrow[1] < $lasttimepoint) {
				// cut the bar at the left side to fit into table
				$start = 0;
			} else {
				$start = ($valrow[1]-$startpunkt)/$faktor;
			}
			$end = ($valrow[2]-$startpunkt)/$faktor;
			$duration = $end - $start;
			// fake a longer time for better visibility
			if ($duration < 2){$duration=2;} 
			// cut the bar at the right side to fit into table
			if (($start+$duration)>$lastpoint) {
				$duration = $lastpoint-$start;
				$shade="light";
			}
			if ($valrow[1] < $lasttimepoint) {
				$shade="light";
			}
			if (isset($status)){
				if ($status == "YES" || $status == "Completed") {
					$barcol = $shade."green";
					$statusmsg = ", Status was OK";
				}else{
					$barcol = $shade."red";
					$statusmsg = ", Status was UNSUCCESSFUL";
				}
			} else {
				$barcol = $shade."grey";
				$statusmsg = "";
			}
			
			if($ii == 1) {
				$out .= "<tr class='d0' width=".$lastpoint.">";
			} else {
				$out .= "<tr class='d1' width=".$lastpoint.">";
				$ii = 0;
			}
			if ($repeatingcol != $valrow[0]){
				$out .= "<td style='color:#000000;'>".$valrow[0]."</td>";
				$repeatingcol =  $valrow[0];
			} else {
				$out .= "<td>".$valrow[0]."</td>";
			}
			if ($valrow[3] != 'Missed') {
				$out .= "<td background=images/tablebg.gif>";
				$out .= "<img src='images/trans.gif' height=1px width=".$start."px />";
				$out .= "<img src='images/".$barcol.".gif' height=".$height."px width=".$duration."px title='".strftime("%H:%M", $valrow[1])." - ".strftime("%H:%M", $valrow[2])." (".$name.", ".$dur."h".$statusmsg.")' />";
			} else {
				$out .= "<td background=images/tablebg.gif bgcolor='#f49090'>";
			}
			$out .= "</td></tr>\n";
			$ii++;
		}
	}
	$out .= generate_timetable_header($startpunkt);
	$out .= "</table>";

	return $out;

}

/**
 * Convert SimpleXMLElement object to array
 * Copyright Daniel FAIVRE 2005 - www.geomaticien.com
 * Copyleft GPL license
 */
function simplexml2array($xml) {
   if (get_class($xml) == 'SimpleXMLElement') {
       $attributes = $xml->attributes();
       foreach($attributes as $k=>$v) {
           if ($v) $a[$k] = (string) $v;
       }
       $x = $xml;
       $xml = get_object_vars($xml);
   }
   if (is_array($xml)) {
       if (count($xml) == 0) return (string) $x; // for CDATA
       foreach($xml as $key=>$value) {
           $r[$key] = simplexml2array($value);
       }
       if (isset($a)) $r['@'] = $a;    // Attributes
       return $r;
   }
   return (string) $xml;
}




function getConfigArray() {

	$configxml = simplexml_load_file("includes/config.xml");
	$queryxml = simplexml_load_file("includes/queries.xml");
        $serverxml = simplexml_load_file("includes/server.xml");

	$configarray = simplexml2array($configxml);
	$queryconfigarray = simplexml2array($queryxml);
	$serverarray = simplexml2array($serverxml);

	$retArray = array();


	//Navigation
        $menuarrayxml = $queryconfigarray["navigation"]["mainmenuitem"];
        $mainmenuarrayxml = $menuarrayxml;
        $menuarray = array();
        $mainmenuarray = array();

        while(list($keymain, $submenu) = each($mainmenuarrayxml)) {
                $menuname = $submenu["name"];
                $menulabel = $submenu["label"];
                $url = "q=overview&m=".$menuname;
                $mainmenuarray[$url] = $menulabel;
        }
        $mainmenuarray["trennlinie"] = "trennlinie";
        //$mainmenuarray["q=admin&m=main"] = "Admin";
        $mainmenuarray["q=serverlist&m=main"] = "Change Server";
        $mainmenuarray["q=logout"] = "Logout";
        $menuarray["main"] = $mainmenuarray;

        while(list($key, $submenu) = each($menuarrayxml)) {
                $menuname = $submenu["name"];
                $menulabel = $submenu["label"];
                $submenuitems = $submenu["submenuitem"];
                $submenuarray = array();
                $submenuarray[""] = "<---";
                while(list($subkey, $submenuitem) = each($submenuitems)) {
                        $submenuitem_name = $submenuitem["name"];
                        $submenuitem_label = $submenuitem["label"];
			if ($submenuitem["notforlibclient"] == "yes") {
				$submenuitem_label .= "_notforlibclient";
			}
                        $url = "q=".$submenuitem_name."&m=".$menuname;
                        $submenuarray[$url] = $submenuitem_label;
                }
		$submenuarray["trennlinie"] = "trennlinie";
		//$submenuarray["q=admin&m=".$submenu["name"]] = "Admin";
		$submenuarray["q=serverlist&m=".$submenu["name"]] = "Change Server";
        	$submenuarray["q=logout"] = "Logout";
                $menuarray[$menuname] = $submenuarray;
        }
	$retArray["menuarray"] = $menuarray;


	//Infoboxes
        $ret = array();
        while(list($key, $submenu) = each($queryconfigarray["indexquery"]["infobox"])) {
		$ret[$submenu["name"]] = $submenu["infoboxitem"];
        }
	$retArray["infoboxarray"] = $ret;


	//Queries
        $ret = array();
        while(list($key, $submenu) = each($queryconfigarray["navigation"]["mainmenuitem"])) {
                $submenuitems = $submenu["submenuitem"];
                while(list($subkey, $submenuitem) = each($submenuitems)) {
			$ret[$submenuitem["name"]] = $submenuitem;
                }
        }
	$retArray["queryarray"] = $ret;
	

	//Colors
        $ret = array();
        while(list($key, $color) = each($configarray["cellcolors"]["color"])) {
                $ret[$color["name"]] = $color["shade"];
        }
	$retArray["colorsarray"] = $ret;
	$retArray["timeout"] = $configarray["timeout"];


	// Servers
        $serverarrayxml = $serverarray["serverarray"]["server"];
	$retArray["defaultserver"] = $serverarray["serverarray"]["defaultserver"];
        $ret = array();
	if (isset($serverarrayxml[1]) && is_array($serverarrayxml[1])) {
		while(list($key, $submenu) = each($serverarrayxml)) {
			$ret[$submenu["servername"]] = $submenu;
		}
	} else {
		$ret[$serverarrayxml["servername"]] = $serverarrayxml;
	}
        $retArray["serverlist"] = $ret;

	return $retArray;
}


?>
