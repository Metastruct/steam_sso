<?php


if (!isset($_REQUEST['ReturnTo'])) {
	die('Missing ReturnTo parameter.');
}

$returnTo = SimpleSAML_Utilities::checkURLAllowed($_REQUEST['ReturnTo']);


if (!preg_match('@State=(.*)@', $returnTo, $matches)) {
	die('Invalid ReturnTo URL for this example.');
}
$stateId = urldecode($matches[1]);

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($stateId);
if (!is_null($sid['url'])) {
	SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

SimpleSAML_Auth_State::loadState($stateId, 'ingameauth:External');

/*
 * The loadState-function will not return if the second parameter does not
 * match the parameter passed to saveState, so by now we know that we arrived here
 * through the ingameauth:External authentication page.
 */


$badUserPass = FALSE;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	if (!isset($_REQUEST['authtkn'])) {
		throw new SimpleSAML_Error_BadRequest('Invalid auth token');
	}

	$authtkn = (string)$_REQUEST['authtkn'];

	SimpleSAML_Utilities::redirectTrustedURL($returnTo, array(
		'authtkn' => $authtkn
	));
	
}


?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>in-game authentication tester</title>
</head>
<body>

<?php if ($badUserPass) { ?>
<p>noauth</p>
<?php } ?>

<form method="post" id="theform" action="?">
<input type="hidden" name="authtkn" id="authtkn" value="">
<input type="hidden" name="ReturnTo" value="<?php echo htmlspecialchars($returnTo); ?>">
<p><input type="submit" id="thesubmit" value="Continue..."></p>
</form>
<script type="text/javascript">
function OnAuthToken(dat)
{
	var form=document.getElementById("theform");
	var authtkn=document.getElementById("authtkn");
	if (dat) {
		authtkn.value = dat;
	}
	form.submit();
	var submit = document.getElementById("thesubmit");
	submit.disabled = true;
	if (dat) {
		submit.value = "Trying to use ingame auth token...";
	} else {
		submit.value = "Redirecting to steam auth...";
	}
}
if ((typeof gmod != 'undefined') && (typeof gmod.reqtoken != 'undefined') ) {
	gmod.reqtoken();
} else {
	console.log("skipping ingameauth...");
	OnAuthToken(false);
}

</script>


</body>
</html>
