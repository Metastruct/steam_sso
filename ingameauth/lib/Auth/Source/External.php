<?php

/**
 * Example external authentication source.
 *
 * This class is an example authentication source which is designed to
 * hook into an external authentication system.
 *
 * To adapt this to your own web site, you should:
 * 1. Create your own module directory.
 * 2. Add a file "default-enable" to that directory.
 * 3. Copy this file and modules/ingameauth/www/resume.php to their corresponding
 *    location in the new module.
 * 4. Replace all occurrences of "ingameauth" in this file and in resume.php with the name of your module.
 * 5. Adapt the getUser()-function, the authenticate()-function and the logout()-function to your site.
 * 6. Add an entry in config/authsources.php referencing your module. E.g.:
 *        'myauth' => array(
 *            '<mymodule>:External',
 *        ),
 *
 * @package simpleSAMLphp
 */
class sspmod_ingameauth_Auth_Source_External extends SimpleSAML_Auth_Source {

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

	}

	public static function hex2str( $hex ) {
	  return pack('H*', $hex);
	}

	public static function str2hex( $str ) {
		$hex = "";
		$i = 0;
		do {
			$hex .= sprintf("%02x", ord($str{$i}));
			$i++;
		} while ($i < strlen($str));
		return $hex;
	}



	
	/**
	 * Retrieve attributes for the user.
	 *
	 * @return array|NULL  The user's attributes, or NULL if the user isn't authenticated.
	 */
	private function getUser() {

	

		if (!isset($_REQUEST['authtkn'])) {
			return NULL;
			//throw new SimpleSAML_Error_BadRequest('Invalid auth token :(');
		} 

		$authtkn = (string)$_REQUEST['authtkn'];
		
		if ($authtkn=="") {
			return NULL;
		}

		$data = sspmod_ingameauth_Auth_Source_External::hex2str($authtkn);

		list($xd, $usr_sid, $timestamp, $signature) = explode("\x00", $data,4);
		$signature_hex = sspmod_ingameauth_Auth_Source_External::str2hex($signature);
		$msg = implode("\x00", array($xd, $usr_sid, $timestamp))."\x00";

		$computed_signature = hash_hmac('sha1', $msg,'');

		$nowt=time();
		if(abs($nowt-$timestamp)>60*10) {
			return NULL;
		}
		if($computed_signature!=$signature_hex) {
			return NULL;
		}
		
		
		/*
		 * Find the attributes for the user.
		 * Note that all attributes in simpleSAMLphp are multivalued, so we need
		 * to store them as arrays.
		 */

		$attributes = array(
			'openid' => array('http://steamcommunity.com/profiles/'.$usr_sid),
			'ingame' => array("1"),
		);

		return $attributes;
	}


	/**
	 * Log in using an external authentication helper.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		$attributes = $this->getUser();
		if ($attributes !== NULL) {

			$state['Attributes'] = $attributes;
			return;
		}

		/*
		 * The user isn't authenticated. We therefore need to
		 * send the user to the login page.
		 */

		/*
		 * First we add the identifier of this authentication source
		 * to the state array, so that we know where to resume.
		 */
		$state['ingameauth:AuthID'] = $this->authId;


		/*
		 * We need to save the $state-array, so that we can resume the
		 * login process after authentication.
		 *
		 * Note the second parameter to the saveState-function. This is a
		 * unique identifier for where the state was saved, and must be used
		 * again when we retrieve the state.
		 *
		 * The reason for it is to prevent
		 * attacks where the user takes a $state-array saved in one location
		 * and restores it in another location, and thus bypasses steps in
		 * the authentication process.
		 */
		$stateId = SimpleSAML_Auth_State::saveState($state, 'ingameauth:External');

		/*
		 * Now we generate a URL the user should return to after authentication.
		 * We assume that whatever authentication page we send the user to has an
		 * option to return the user to a specific page afterwards.
		 */
		$returnTo = SimpleSAML_Module::getModuleURL('ingameauth/resume.php', array(
			'State' => $stateId,
		));

		/*
		 * Get the URL of the authentication page.
		 *
		 * Here we use the getModuleURL function again, since the authentication page
		 * is also part of this module, but in a real example, this would likely be
		 * the absolute URL of the login page for the site.
		 */
		$authPage = SimpleSAML_Module::getModuleURL('ingameauth/authpage.php');

		/*
		 * The redirect to the authentication page.
		 *
		 * Note the 'ReturnTo' parameter. This must most likely be replaced with
		 * the real name of the parameter for the login page.
		 */
		SimpleSAML_Utilities::redirectTrustedURL($authPage, array(
			'ReturnTo' => $returnTo,
		));

		/*
		 * The redirect function never returns, so we never get this far.
		 */
		assert('FALSE');
	}


	/**
	 * Resume authentication process.
	 *
	 * This function resumes the authentication process after the user has
	 * entered his or her credentials.
	 *
	 * @param array &$state  The authentication state.
	 */
	public static function resume() {

	
	
		/*
		 * First we need to restore the $state-array. We should have the identifier for
		 * it in the 'State' request parameter.
		 */
		if (!isset($_REQUEST['State'])) {
			throw new SimpleSAML_Error_BadRequest('Missing "State" parameter.');
		}
		
		
		$stateId = (string)$_REQUEST['State'];

		// sanitize the input
		$sid = SimpleSAML_Utilities::parseStateID($stateId);
		if (!is_null($sid['url'])) {
			SimpleSAML_Utilities::checkURLAllowed($sid['url']);
		}

		/*
		 * Once again, note the second parameter to the loadState function. This must
		 * match the string we used in the saveState-call above.
		 */
		$state = SimpleSAML_Auth_State::loadState($stateId, 'ingameauth:External');

		/*
		 * Now we have the $state-array, and can use it to locate the authentication
		 * source.
		 */
		$source = SimpleSAML_Auth_Source::getById($state['ingameauth:AuthID']);
		if ($source === NULL) {
			/*
			 * The only way this should fail is if we remove or rename the authentication source
			 * while the user is at the login page.
			 */
			throw new SimpleSAML_Error_Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}

		/*
		 * Make sure that we haven't switched the source type while the
		 * user was at the authentication page. This can only happen if we
		 * change config/authsources.php while an user is logging in.
		 */
		if (! ($source instanceof self)) {
			throw new SimpleSAML_Error_Exception('Authentication source type changed.');
		}


		/*
		 * OK, now we know that our current state is sane. Time to actually log the user in.
		 *
		 * First we check that the user is acutally logged in, and didn't simply skip the login page.
		 */
		$attributes = $source->getUser();
		if ($attributes === NULL) {
			$authId = "steam";

			$source = SimpleSAML_Auth_Source::getById($authId);
			try {
				$source->authenticate($state);
			} catch (SimpleSAML_Error_Exception $e) {
				SimpleSAML_Auth_State::throwException($state, $e);
			} catch (Exception $e) {
				$e = new SimpleSAML_Error_UnserializableException($e);
				SimpleSAML_Auth_State::throwException($state, $e);
			}
			// fallBack never returns after loginCompleted()
			SimpleSAML_Logger::debug('Ingameauth: backend returned');
			self::loginCompleted($state);
			return;
		}

		/*
		 * So, we have a valid user. Time to resume the authentication process where we
		 * paused it in the authenticate()-function above.
		 */

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);

		/*
		 * The completeAuth-function never returns, so we never get this far.
		 */
		assert('FALSE');
	}


	//public function logout(&$state) {
	//	assert('is_array($state)');
	//}

}
