<?php

require_once('sso.php');

function sso_isadmin($steamid) {
	
	$adminsurl   = "http://steamcommunity.com/groups/metastruct_admins/memberslistxml/?xml=1";
	$cache_file  = dirname(__FILE__) . "/metastruct_admins.xml";
	$cachefailed = false;
	
	if (file_exists($cache_file) && (filesize($cache_file) > 0) && (filemtime($cache_file) > (time() - 60 * 15))) {
		// cache age?
	} else {
		// Our cache is out-of-date, so load the data from our remote server,
		// and also save it over our cache for next time.
		
		$ctx = stream_context_create(array(
			'http' => array(
				'timeout' => 5
			)
		));
		
		
		$file = @file_get_contents($adminsurl, false, $ctx);
		
		$bad = (!$file) || (strlen($file) < 10) || (strpos($file, 'Sorry') !== false) || !simplexml_load_string($file);
		
		if ($bad) {
			$cachefailed = true;
			if (!file_exists($cache_file) || filesize($cache_file) < 5) {
				die("Unable to fetch admin list and cache data was bad");
			}
		} else {
			file_put_contents($cache_file, $file, LOCK_EX);
		}
	}
	
	
	$xml = new XMLReader();
	if (file_exists($cache_file) && filesize($cache_file) > 0) {
		$xml->open($cache_file);
	} else {
		die("no cache");
		//$xml->open($adminsurl);
	}
	
	$doc = new DOMDocument;
	
	$foundvalid = false;
	while ($xml->read()) {
		if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'members') {
			$xml->moveToElement();
			while ($xml->read()) {
				if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'steamID64') {
					$sid64 = $xml->readString();
					if ($sid64 == $steamid) {
						$foundvalid = true;
						break;
					}
				}
			}
			break;
		}
	}
	
	$xml->close();
	
	
	return $foundvalid;
	
}


function sso_requireadmin() {
	
	$S = SteamSSO::sso();
	$S->login();
	
	$steamid = $S->steamid();
	
	$isadmin = sso_isadmin($steamid);
	
	if (!$isadmin) {
		die('Access denied');
	}
	
	return $steamid;
	
}
