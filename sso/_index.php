<?php

	$err = error_reporting(E_ALL);
		require_once("sso.php");
	error_reporting($err);
	
	//$steamid64 = sso_login();
	function sso_redir() {
		if (isset($_GET['redir'])) {
			header('Location: ' . $_GET['redir'] );
		} else {
			header('Location: /');
		}
		exit();
	}
		// logout the fella
	if (isset($_GET['logout'])) {
		sso_logout(false);
		sso_redir();
	}
	if (isset($_GET['login'])) {
		if (!sso_getuser()) 
			sso_login();
		sso_redir();
	}	
	// server token fetcher
	if (isset($_GET['steamid'])) {
		$authkey = sso_req_authkey($_GET['steamid']);
		echo 'Key:'.$authkey;
		exit();
	}
	
	
?>
<b>Meta Construct Steam Sign On System</b><br/>
<?php	
	$steamid = sso_getuser();
	if ($steamid) {
?>

<a href="?logout">logout<a/> 
<br/>
SteamID: <a link href="http://steamcommunity.com/profile/<?php echo $steamid; ?>"><?php echo $steamid; ?></a>
<br/>

<?php $info = sso_get_info(); ?>
Hello, <?php echo $info['personaname']; ?>!
<br/>
<img src="<?php echo $info['avatarfull']; ?>" />
<?php } else {?>
<a href="?login">Login<a/> with steam!
<?php } ?>
.