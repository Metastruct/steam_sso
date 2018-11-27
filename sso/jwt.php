<?php

header("Content-Security-Policy: frame-ancestors www.metastruct.net metastruct.net ssso.metastruct.net www3.metastruct.net g1cf.metastruct.net g2cf.metastruct.net");

require_once('sso.php');


//error_reporting(E_ALL);
//ini_set('display_errors','On');

$S = SteamSSO::sso();
$S->login();
$steamid=$S->steamid();

$privateKey = file_get_contents("../../ssojwt_privatekey.rsa");

$isadmin = false;

if (isset($_REQUEST['admin']) && $_REQUEST['admin']) {
	if ($_REQUEST['admin'] === "2") {
		require_once('admins.php');
		$isadmin = sso_isadmin($steamid);
	else {
		require_once('admins.php');
		sso_requireadmin();
		$isadmin = true;
	}
}

if (!isset($_REQUEST['redirect_to'])) {
        ?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Metastruct Authentication Endpoint for JWT.</title>
  </head>
  <body>
You should not see this normally. Either you are poking at our files or there was a programming error.
  </body>
</html>
<?php
        exit(0);
}




/**
 * Reverse the output of native PHP parse_url()
 * @param string[] $url An array with a structure similar to the return value of parse_url()
 * @return string The rebuilt URL
 */
function unparse_url($url)
{
        $scheme = isset($url["scheme"]) ? $url["scheme"] . ":" : "";
        $user = isset($url["user"]) ? rawurlencode($url["user"]) : "";
        $pass = isset($url["pass"]) ? ":" . rawurlencode($url["pass"]) : "";
        $at = strlen($user.$pass) ? "@" : "";
        $host = isset($url["host"]) ? rawurlencode($url["host"]) : "";
        $double_slash = strlen($at.$host) ? "//" : "";
        $port = isset($url["port"]) ? ":" . $url["port"] : "";
        $path = isset($url["path"]) ? $url["path"] : "";
        $query = isset($url["query"]) ? "?" . $url["query"] : "";
        $fragment = isset($url["fragment"]) ? "#" . $url["fragment"] : "";
        return $scheme.$double_slash.$user.$pass.$at.$host.$port.$path.$query.$fragment;
}


function Redirect($url, $permanent = false)
{
    if (headers_sent() === false)
    {
        header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
    }
    exit();
}


require __DIR__ . '/vendor/autoload.php';

use \Firebase\JWT\JWT;

$token = array(
    "iat" => time(),
    "exp" => time() + 3600,
    "steamid" => $steamid . ""
);

if ($isadmin) {
	$token["admin"] = true;
}

if ($S->ingame()) {
        $token['ingame'] = true;
}

$jwt = JWT::encode($token, $privateKey, 'RS256');

$url = $_GET["redirect_to"];

$query = parse_url($url);

$redir_host = $query["host"];


if (!$query) {
        die("Invalid redirect URL");
}

$redir_hosts = array("www3.metastruct.net","sso.metastruct.net","g1cf.metastruct.net","g2cf.metastruct.net","g3cf.metastruct.net");
if (!in_array($redir_host,$redir_hosts)) {
        die("invalid redirect host");
}

$queryParams = array();
        parse_str($query['query'], $queryParams);
        $queryParams['token'] = "$jwt";
        $query['query'] = http_build_query($queryParams);

$url = unparse_url($query);

//echo $url;
//exit;

//if (strlen($url) > 2048) {
//        error_log('Redirecting to a URL longer than 2048 bytes.');
//}

Redirect($url);
