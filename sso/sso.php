<?php


require_once( dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'simplesaml_sp' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR .'_autoload.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bcbitwise.php');
class SteamSSO
{

	protected function __construct() {}
	protected function __clone() {}
	public function __wakeup()
	{
		throw new Exception("Cannot unserialize singleton");
	}

	public static $as = NULL;
	protected static $inst = null;
	private static $steamapikey = '';
	public static function sso() {
		
		if(!isset(self::$as)) {
			self::$as = new SimpleSAML_Auth_Simple('default-sp');
		}
		
		if (self::$inst === null) {
			self::$inst = new SteamSSO();
		}
		return self::$inst;
		
	}
	
	
	public static function steamid() {
		$attributes = self::$as->getAttributes();
		$steamid=@$attributes['openid'][0];
		$steamid=(isset($steamid) && $steamid)?self::getsid($steamid):false;
		return $steamid;
	}
	
	public static function ingame() {
		$attributes = self::$as->getAttributes();
		$ingame=@$attributes['ingame'];
		if (!isset($ingame)) { return false; };
		$ingame=$ingame[0];
		if (isset($ingame) && $ingame) {
			return true;
		};
		return false;
	}

	public static function getsid($url) {
		return substr(strrchr($url, "/"), 1);
	}
	
	// community id to steamid
	public static function sid32($i64friendID)
	{
		$tmpfriendID = $i64friendID;
		$iServer = "1";
		if(bcmod($i64friendID, "2") == "0")
		{
			$iServer = "0";
		}
		$tmpfriendID = bcsub($tmpfriendID,$iServer);
		if(bccomp("76561197960265728",$tmpfriendID) == -1)
			$tmpfriendID = bcsub($tmpfriendID,"76561197960265728");
		$tmpfriendID = bcdiv($tmpfriendID, "2");
		return ("STEAM_0:" . $iServer . ":" . $tmpfriendID);
	}
	public static function accid($steamid64) {
		$accountid = floatval(bcand($steamid64,"4294967295"));
		return $accountid;
	}
	// steamid to community id
	public static function sid64($steamid) {
		$parts = explode(':', str_replace('STEAM_', '' ,$steamid)); 
		return bcadd(bcadd('76561197960265728', $parts['1']), bcmul($parts['2'], '2')); 
	}
	public static function login() {
		self::$as->requireAuth();	
	}
	public static function logout() {
		self::$as->logout(SimpleSAML_Utilities::selfURLNoQuery());
	}
	



	// public
	private static $info_cached=false;
	private static $info_cache=false;
	public static function info($steamid) {
		if (self::$info_cached===$steamid && self::$info_cache) {
			return self::$info_cache;
		}
		
		$cacheurl = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR .  "/sso_cache/" . $steamid . '.json';
		$res = false;
		
		if (!$steamid) { return false; }
		
		// community data
		$cdata = @file_get_contents($cacheurl);
		if ($cdata) {
			if ((filemtime($cacheurl) < strtotime ("-2 days")) || strlen($cdata)<5){
				$cdata=false;
			}
		}
		
		// fetch really
		$new_cache = false;
		if (!$cdata) {
			$url = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . self::$steamapikey . '&steamids=' . $steamid . '&format=json';
			$cdata = @file_get_contents($url,false,stream_context_create(
				array(
					'http' => array(
						'timeout' => 20
					)
			)));
			
			if ($cdata && (strlen($cdata)>5)) {
				$new_cache = true;
			} else {
				$cdata = @file_get_contents($cacheurl);
				if (!$cdata) {
					return false;
				};
			}
		}
		
		if (!$cdata) return false;
		$array = json_decode($cdata, true);
		if (!$array) return false;
		
		if ($new_cache) {
			file_put_contents($cacheurl , $cdata,LOCK_EX);
		}
		
		$res = $array['response']['players'][0]; 
		self::$info_cached = $steamid;
		self::$info_cache = $res;
		
		return $res;
	}
	public static function get_aliases($steamid) {
		$info = self::info($steamid);
		$json = @file_get_contents($info['profileurl'].'ajaxaliases');
		if (!$json) return false;
		$names = json_decode($json,true);
		if (!$names) return false;
		$r=Array();
		foreach ($names as $key => $data) {
			$str = $data['timechanged'];
			$arr = explode(" @ ",$str);
			$date = $arr[0];
			$time = $arr[1];
			$date = strtotime($date);
			$time = strtotime($time,$date);

			$timestamp=$time;
			
			$value = $data['newname'];
			$key = $timestamp;
			if (@!$r[$key]) $r[$key]=$value; // screw too many names in ONE minute...
		}
		return $r;
	}
} // class
