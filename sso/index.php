<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once('sso.php');


error_reporting(E_ALL);
ini_set('display_errors','On');





$S = sso::sso();
$steamid=$S->steamid();

	if (isset($_REQUEST['login'])) 	$S->login();
	
	if (isset($_REQUEST['logout'])) $S->logout();
	

	if (isset($_REQUEST['aliases'])) {
		if($steamid) 
			echo json_encode($S->get_aliases($steamid));
		exit();
	}
	
?>
<html><head>
<script type="text/javascript">

 function ajaxRequest(){
  var activexmodes=["Msxml2.XMLHTTP", "Microsoft.XMLHTTP"] //activeX versions to check for in IE
  if (window.ActiveXObject){ //Test for support for ActiveXObject in IE first (as XMLHttpRequest in IE7 is broken)
   for (var i=0; i<activexmodes.length; i++){
    try{
     return new ActiveXObject(activexmodes[i])
    }
    catch(e){
     //suppress error
    }
   }
  }
  else if (window.XMLHttpRequest) // if Mozilla, Safari etc
   return new XMLHttpRequest()
  else
   return false
 }

 var mygetrequest=new ajaxRequest()
 mygetrequest.onreadystatechange=function(){
  if (mygetrequest.readyState==4){
   if (mygetrequest.status==200 || window.location.href.indexOf("http")==-1){
   var jsondata=eval("("+mygetrequest.responseText+")") //retrieve result as an JavaScript object
	var output=""
	for (var key in jsondata){
	 output='<li>'+jsondata[key]+' <i>('+Math.round(((new Date()).getTime()*0.001-key)/60/60/24)+' days ago)</i></li>'+output
    }
    output='<ul>'+output+'</ul>'
    document.getElementById("aliases").innerHTML=output
   }
   else{
    alert("An error has occured making the request")
   }
  }
 }

 mygetrequest.open("GET", "?aliases", true)
 mygetrequest.send(null)

 </script>

</head><body>
<h2>Meta Construct - Steam Sign On</h2>
<?php	
	if ($steamid) {
?>

<a href="?logout">logout<a/> 
<br/>
SteamID ( <?php echo $S->sid32($steamid);  ?> ): <a link href="http://steamcommunity.com/profile/<?php echo $steamid; ?>"><?php echo $steamid; ?></a>
<br/>
AccountID: <?php echo $S->accid($steamid); ?>
<br/>

<?php $info = $S->info($steamid); ?>
Hello, <?php echo $info['personaname']; ?>!
<br/>
<img src="<?php echo $info['avatarfull']; ?>" /><br/>
<h3>Info:</h3>
<table border="0"> 

<?php

function addlinks($s) {
    return preg_replace('/https?:\/\/[\w\-\.!~?&+\*\'"(),\/]+/','<a href="$0">$0</a>',$s);
}
function unix_timestamp_to_human ($timestamp = "", $format = 'D d M Y - H:i:s')
{
     if (empty($timestamp) || ! is_numeric($timestamp)) $timestamp = time();
     return ($timestamp) ? date($format, $timestamp) : date($format, $timestamp);
}
function infohtml($k,$v) {
	
	switch ($k) {
		case "personastate":
			switch ($v) {
				case 0:
					return "Offline"; 
				case 1:
					return "Online"; 
				case 2:
					return "Busy"; 
				case 3:
					return "Away"; 
				case 4:
					return "Snooze"; 
				case 5:
					return "Looking to trade";
				case 6:
					return "Looking to play";
				default:
					return "Unknown";
			}
		case "communityvisibilitystate":
			switch ($v) {
				case 0:
					return "???"; 
				case 1:
					return "Private"; 
				case 2:
					return "Friends only"; 
				case 3:
					return "Friends of friends only"; 
				case 4:
					return "users only"; 
				case 5:
					return "Public trade";
			}
		case "lastlogoff":
			return unix_timestamp_to_human($v);
			break;
		case "timecreated":
			return unix_timestamp_to_human($v);
			break;
		case "primaryclanid":
			return '<a href="http://steamcommunity.com/gid/'.$v.'">'.$v.'</a>'; 
			break;
	}
	return addlinks($v)==$v?htmlentities($v):addlinks($v);
	
}
?>

<?php foreach ($S->info($steamid) as $key => $value) { ?>
<tr><td><b><?=$key?></b></td><td><?=infohtml($key,$value)?></td></tr>
<?php } ?>
</table>

<h3>Previous names:</h3>
<div id="aliases"> 
</div>

<?php
} else {?>
<a href="?login"><img src="http://steamcommunity.com/public/images/signinthroughsteam/sits_landing.png" /><a/>
<?php } ?>


</body></html>