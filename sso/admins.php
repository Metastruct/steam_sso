<?php  

require_once('sso.php');

function sso_requireadmin() {
	
	$S = SteamSSO::sso();
	$steamid=$S->steamid();

	$S->login();

	$adminsurl="http://steamcommunity.com/groups/metastruct_admins/memberslistxml/?xml=1";
	$cache_file="/home/metastru/sso_cache/metastruct_admins.xml";
	$cachefailed = false;

	if (file_exists($cache_file) && (filesize($cache_file)>0) && (filemtime($cache_file) > (time() - 60 * 15 ))) {

	} else {
		// Our cache is out-of-date, so load the data from our remote server,
		// and also save it over our cache for next time.
		
		$ctx = stream_context_create(array('http'=>
			array(
				'timeout' => 20,
			)
		));
		
		
		$file = @file_get_contents($adminsurl,false,$ctx);

		$bad = (!$file) || (strlen($file)<10) || (strpos($file, 'Sorry') !== false) || !simplexml_load_string($file);

		if ($bad) { 
			$cachefailed=true;
			if (!file_exists($cache_file) || filesize($cache_file)<5) {
				die("Unable to fetch admin list and new data was bad");
			}
		} else {
			file_put_contents($cache_file, $file, LOCK_EX);
			}
	}


	$xml = new XMLReader();
	if (file_exists($cache_file) && filesize($cache_file)>0) {
		$xml->open($cache_file);
	} else {
		die("no cache");
		$xml->open($adminsurl);
	}

	$doc = new DOMDocument;

	$foundvalid = false;
	while($xml->read()) {
		if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'members') {
			$xml->moveToElement();
			while($xml->read()) {
				if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'steamID64') {
					$sid64 = $xml->readString();
					if ($sid64==$steamid) {
						$foundvalid = true;
						break;
					}
				}
			}
			break;
		}
	}

	$xml->close();


	if (!$foundvalid) {
		die('Access denied');
	}

	return $steamid;

}