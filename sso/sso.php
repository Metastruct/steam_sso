<?php
	require_once( dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'simplesaml' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR .'_autoload.php');
	require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bcbitwise.php');
	class sso
	{
		public static $instance = NULL;
		public static $as = NULL;
		public static function sso() {
			if(!isset(self::$instance)) {
				self::$instance = new sso();
			}
			return self::$instance;
		}
		private function __construct() {
			self::$as = new SimpleSAML_Auth_Simple('default-sp');
		} 
		
		public static function steamid() {
			$attributes = self::$as->getAttributes();
			$steamid=@$attributes['openid'][0];
			$steamid=(isset($steamid) && $steamid)?self::getsid($steamid):false;
			return $steamid;
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
			if (self::$info_cached===$steamid && self::$info_cache) return self::$info_cache; // security?
			//$steamid = sso_getuser();
			$cacheurl = "/home/ms/sso_cache/" . $steamid . '.json';
			$res = false;
			if ($steamid) {
				$link = @file_get_contents($cacheurl);
				if ($link) {
					if (filemtime($cacheurl) < strtotime ("-2 days") ){
						$link=false;
					}
				}
				if (!$link) {
					$key = '715F450CD97E542F7ABBB7DD669D8626';
					// make nonblocking some way :x
					$link = file_get_contents(
						'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $key . '&steamids=' . $steamid . '&format=json',
						false,
						stream_context_create(
							array(
								'http' => array(
									'ignore_errors' => true
								)
							)
						)
					);
					file_put_contents($cacheurl , $link,LOCK_EX);
				}
				if (!$link) return false;
				$array = json_decode($link, true);
				$res = $array['response']['players'][0]; 
				self::$info_cached = $steamid;
				self::$info_cache = $res;
			}
			return $res;
		}
		public static function get_aliases($steamid) {
			$info = self::info($steamid);
			$json = file_get_contents($info['profileurl'].'ajaxaliases');
			$names = json_decode($json,true);
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
	
?>
