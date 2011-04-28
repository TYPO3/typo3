<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Dmitry Dulepov <dmitry@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   57: class tx_openid_sv1 extends t3lib_svbase
 *   92:     public function init()
 *  119:     public function initAuth($subType, array $loginData, array $authenticationInformation, t3lib_userAuth &$parentObject)
 *  139:     public function getUser()
 *  176:     public function authUser(array $userRecord)
 *  221:     protected function includePHPOpenIDLibrary()
 *  250:     protected function getUserRecord($openIDIdentifier)
 *  273:     protected function getOpenIDConsumer()
 *  300:     protected function sendOpenIDRequest()
 *  368:     protected function getReturnURL()
 *  414:     protected function writeLog($message)
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib . 'class.t3lib_svbase.php');
require_once(t3lib_extMgm::extPath('openid', 'sv1/class.tx_openid_store.php'));

/**
 * Service "OpenID Authentication" for the "openid" extension.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_openid
 */
class tx_openid_sv1 extends t3lib_svbase {
	/** Class name */
	public $prefixId = 'tx_openid_sv1';		// Same as class name

	/** Path to this script relative to the extension directory */
	public $scriptRelPath = 'sv1/class.tx_openid_sv1.php';

	/** The extension key */
	public $extKey = 'openid';

	/** Login data as passed to initAuth() */
	protected $loginData = array();

	/**
	 * Additional authentication information provided by t3lib_userAuth. We use
	 * it to decide what database table contains user records.
	 */
	protected $authenticationInformation = array();

	/**
	 * OpenID response object. It is initialized when OpenID provider returns
	 * with success/failure response to us.
	 *
	 * @var	Auth_OpenID_ConsumerResponse
	 */
	protected $openIDResponse = null;

	/**
	 * A reference to the calling object
	 *
	 * @var	t3lib_userAuth
	 */
	protected $parentObject;

	/**
	 * If set to TRUE, than libraries are already included.
	 */
	protected static $openIDLibrariesIncluded = false;

	/**
	 * Contructs the OpenID authentication service.
	 */
	public function __construct() {
		// Auth_Yadis_Yadis::getHTTPFetcher() will use a cURL fetcher if the functionality
		// is available in PHP, however the TYPO3 setting is not considered here:
		if (!defined('Auth_Yadis_CURL_OVERRIDE')) {
			if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']) {
				define('Auth_Yadis_CURL_OVERRIDE', TRUE);
			}
		}
	}

	/**
	 * Checks if service is available,. In case of this service we check that
	 * prerequesties for "PHP OpenID" libraries are fulfilled:
	 * - GMP or BCMATH PHP extensions are installed and functional
	 * - set_include_path() PHP function is available
	 *
	 * @return	boolean		TRUE if service is available
	 */
	public function init() {
		$available = false;
		if (extension_loaded('gmp')) {
			$available = is_callable('gmp_init');
		} elseif (extension_loaded('bcmath')) {
			$available = is_callable('bcadd');
		} else {
			$this->writeLog('Neither bcmath, nor gmp PHP extension found. OpenID authentication will not be available.');
		}
		// We also need set_include_path() PHP function
		if (!is_callable('set_include_path')) {
			$available = false;
			$this->writeDevLog('set_include_path() PHP function is not available. OpenID authentication is disabled.');
		}
		return $available ? parent::init() : false;
	}

	/**
	 * Initializes authentication for this service.
	 *
	 * @param	string			$subType: Subtype for authentication (either "getUserFE" or "getUserBE")
	 * @param	array			$loginData: Login data submitted by user and preprocessed by t3lib/class.t3lib_userauth.php
	 * @param	array			$authenticationInformation: Additional TYPO3 information for authentication services (unused here)
	 * @param	t3lib_userAuth	$parentObject: Calling object
	 * @return	void
	 */
	public function initAuth($subType, array $loginData, array $authenticationInformation, t3lib_userAuth &$parentObject) {
		// Store login and authetication data
		$this->loginData = $loginData;
		$this->authenticationInformation = $authenticationInformation;
		// If we are here after authentication by the OpenID server, get its response.
		if (t3lib_div::_GP('tx_openid_mode') == 'finish' && $this->openIDResponse == null) {
			$this->includePHPOpenIDLibrary();
			$openIDConsumer = $this->getOpenIDConsumer();
			$this->openIDResponse = $openIDConsumer->complete($this->getReturnURL());
		}
		$this->parentObject = $parentObject;
	}

	/**
	 * This function returns the user record back to the t3lib_userAuth. it does not
	 * mean that user is authenticated, it means only that user is found. This
	 * function makes sure that user cannot be authenticated by any other service
	 * if user tries to use OpenID to authenticate.
	 *
	 * @return	mixed		User record (content of fe_users/be_users as appropriate for the current mode)
	 */
	public function getUser() {
		$userRecord = null;
		if ($this->loginData['status'] == 'login') {
			if ($this->openIDResponse instanceof Auth_OpenID_ConsumerResponse) {
				$GLOBALS['BACK_PATH'] = $this->getBackPath();
				// We are running inside the OpenID return script
				// Note: we cannot use $this->openIDResponse->getDisplayIdentifier()
				// because it may return a different identifier. For example,
				// LiveJournal server converts all underscore characters in the
				// original identfier to dashes.
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					$openIDIdentifier = $this->getFinalOpenIDIdentifier();
					if ($openIDIdentifier) {
						$userRecord = $this->getUserRecord($openIDIdentifier);
						if ($userRecord != null) {
							$this->writeLog('User \'%s\' logged in with OpenID \'%s\'',
								$userRecord[$this->parentObject->formfield_uname], $openIDIdentifier);
						} else {
							$this->writeLog('Failed to login user using OpenID \'%s\'',
								$openIDIdentifier);
						}
					}
				}
			} else {
				// Here if user just started authentication
				$userRecord = $this->getUserRecord($this->loginData['uname']);
			}
			// The above function will return user record from the OpenID. It means that
			// user actually tried to authenticate using his OpenID. In this case
			// we must change the password in the record to a long random string so
			// that this user cannot be authenticated with other service.
			if (is_array($userRecord)) {
				$userRecord[$this->authenticationInformation['db_user']['userident_column']] = uniqid($this->prefixId . LF, TRUE);
			}
		}
		return $userRecord;
	}

	/**
	 * Authenticates user using OpenID.
	 *
	 * @param	array		$userRecord	User record
	 * @return	int		Code that shows if user is really authenticated.
	 * @see	t3lib_userAuth::checkAuthentication()
	 */
	public function authUser(array $userRecord) {
		$result = 0;	// 0 means authentication failure

		if ($userRecord['tx_openid_openid'] == '') {
			// If user does not have OpenID, let other services to try (code 100)
			$result = 100;
		} else {
			// Check if user is identified by the OpenID
			if ($this->openIDResponse instanceof Auth_OpenID_ConsumerResponse) {
				// If we have a response, it means OpenID server tried to authenticate
				// the user. Now we just look what is the status and provide
				// corresponding response to the caller
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					// Success (code 200)
					$result = 200;
				} else {
					$this->writeDevLog('OpenID authentication failed with code \'%s\'.',
							$this->openIDResponse->status);
				}
			} else {
				// We may need to send a request to the OpenID server.
				// Check if the user identifier looks like OpenID user identifier first.
				// Prevent PHP warning in case if identifiers is not an OpenID identifier
				// (not an URL).
				$urlParts = @parse_url($this->loginData['uname']);
				if (is_array($urlParts) && $urlParts['scheme'] != '' && $urlParts['host']) {
					// Yes, this looks like a good OpenID. Ask OpenID server (should not return)
					$this->sendOpenIDRequest();
					// If we are here, it means we have a valid OpenID but failed to
					// contact the server. We stop authentication process.
					// Alternatively it may mean that OpenID format is not correct.
					// In both cases we return code 0 (complete failure)
				} else {
					$result = 100;
				}
			}
		}

		return $result;
	}

	/**
	 * Includes necessary files for the PHP OpenID library
	 *
	 * @return	void
	 */
	protected function includePHPOpenIDLibrary() {
		if (!self::$openIDLibrariesIncluded) {

			// Prevent further calls
			self::$openIDLibrariesIncluded = TRUE;

			// PHP OpenID libraries requires adjustments of path settings
			$oldIncludePath = get_include_path();
			$phpOpenIDLibPath = t3lib_extMgm::extPath('openid') . 'lib/php-openid';
			@set_include_path($phpOpenIDLibPath . PATH_SEPARATOR .
							$phpOpenIDLibPath . PATH_SEPARATOR . 'Auth' .
							PATH_SEPARATOR . $oldIncludePath);

			// Make sure that random generator is properly set up. Constant could be
			// defined by the previous inclusion of the file
			if (!defined('Auth_OpenID_RAND_SOURCE')) {
				if (TYPO3_OS == 'WIN') {
					// No random generator on Windows!
					define('Auth_OpenID_RAND_SOURCE', null);
				} elseif (!is_readable('/dev/urandom')) {
					if (is_readable('/dev/random')) {
						define('Auth_OpenID_RAND_SOURCE', '/dev/random');
					} else {
						define('Auth_OpenID_RAND_SOURCE', null);
					}
				}
			}

			// Include files
			require_once($phpOpenIDLibPath . '/Auth/OpenID/Consumer.php');

			// Restore path
			@set_include_path($oldIncludePath);

			if (!is_array($_SESSION)) {
				// Yadis requires session but session is not initialized when
				// processing Backend authentication
				@session_start();
				$this->writeLog('Session is initialized');
			}
		}
	}

	/**
	 * Gets user record for the user with the OpenID provided by the user
	 *
	 * @param	string		$openIDIdentifier	OpenID identifier to search for
	 * @return	array		Database fields from the table that corresponds to the current login mode (FE/BE)
	 */
	protected function getUserRecord($openIDIdentifier) {
		$record = null;
		if ($openIDIdentifier) {
			$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*',
				$this->authenticationInformation['db_user']['table'],
				'tx_openid_openid=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($openIDIdentifier, $this->authenticationInformation['db_user']['table']) .
					$this->authenticationInformation['db_user']['check_pid_clause'] .
					$this->authenticationInformation['db_user']['enable_clause']);
		} else {
			// This should never happen and generally means hack attempt.
			// We just log it and do not return any records.
			$this->writeLog('getUserRecord is called with the empty OpenID');
		}
		return $record;
	}

	/**
	 * Creates OpenID Consumer object with a TYPO3-specific store. This function
	 * is almost identical to the example from the PHP OpenID library.
	 * @todo use DB (or the caching framework) instead of the filesystem to store OpenID data
	 * @return	Auth_OpenID_Consumer		Consumer instance
	 */
	protected function getOpenIDConsumer() {
		$openIDStore = t3lib_div::makeInstance('tx_openid_store');
		/* @var $openIDStore tx_openid_store */
		$openIDStore->cleanup();

		return new Auth_OpenID_Consumer($openIDStore);
	}

	/**
	 * Sends request to the OpenID server to authenticate the user with the
	 * given ID. This function is almost identical to the example from the PHP
	 * OpenID library. Due to the OpenID specification we cannot do a slient login.
	 * Sometimes we have to redirect to the OpenID provider web site so that
	 * user can enter his password there. In this case we will redirect and provide
	 * a return adress to the special script inside this directory, which will
	 * handle the result appropriately.
	 *
	 * This function does not return on success. If it returns, it means something
	 * went totally wrong with OpenID.
	 *
	 * @return	void
	 */
	protected function sendOpenIDRequest() {
		$this->includePHPOpenIDLibrary();

		$openIDIdentifier = $this->loginData['uname'];

		// Initialize OpenID client system, get the consumer
		$openIDConsumer = $this->getOpenIDConsumer();

		// Begin the OpenID authentication process
		$authenticationRequest = $openIDConsumer->begin($openIDIdentifier);
		if (!$authenticationRequest) {
			// Not a valid OpenID. Since it can be some other ID, we just return
			// and let other service handle it.
			$this->writeLog('Could not create authentication request for OpenID identifier \'%s\'', $openIDIdentifier);
			return;
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.

		// For OpenID version 1, we *should* send a redirect. For OpenID version 2,
		// we should use a Javascript form to send a POST request to the server.
		$returnURL = $this->getReturnURL();
		$trustedRoot = t3lib_div::getIndpEnv('TYPO3_SITE_URL');

	    if ($authenticationRequest->shouldSendRedirect()) {
			$redirectURL = $authenticationRequest->redirectURL($trustedRoot, $returnURL);

			// If the redirect URL can't be built, return. We can only return.
			if (Auth_OpenID::isFailure($redirectURL)) {
				$this->writeLog('Authentication request could not create redirect URL for OpenID identifier \'%s\'', $openIDIdentifier);
				return;
			}

			// Send redirect. We use 303 code because it allows to redirect POST
			// requests without resending the form. This is exactly what we need here.
			// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.4
			@ob_end_clean();
			t3lib_utility_Http::redirect($redirectURL, t3lib_utility_Http::HTTP_STATUS_303);
		} else {
			$formHtml = $authenticationRequest->htmlMarkup($trustedRoot,
							$returnURL, false, array('id' => 'openid_message'));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure($formHtml)) {
				// Form markup cannot be generated
				$this->writeLog('Could not create form markup for OpenID identifier \'%s\'', $openIDIdentifier);
				return;
			} else {
				@ob_end_clean();
				echo $formHtml;
			}
		}
		// If we reached this point, we must not return!
		exit;
	}

	/**
	 * Creates return URL for the OpenID server. When a user is authenticated by
	 * the OpenID server, the user will be sent to this URL to complete
	 * authentication process with the current site. We send it to our script.
	 *
	 * @return	string		Return URL
	 */
	protected function getReturnURL() {
		if ($this->authenticationInformation['loginType'] == 'FE') {
			// We will use eID to send user back, create session data and
			// return to the calling page.
			// Notice: 'pid' and 'logintype' parameter names cannot be changed!
			// They are essential for FE user authentication.
			$returnURL = 'index.php?eID=tx_openid&' .
						'pid=' . $this->authenticationInformation['db_user']['checkPidList'] . '&' .
						'logintype=login&';
		} else {
			// In the Backend we will use dedicated script to create session.
			// It is much easier for the Backend to manage users.
			// Notice: 'login_status' parameter name cannot be changed!
			// It is essential for BE user authentication.
			$absoluteSiteURL = substr(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')));
			$returnURL = $absoluteSiteURL . TYPO3_mainDir . 'sysext/' . $this->extKey . '/class.tx_openid_return.php?login_status=login&';
		}
		if (t3lib_div::_GP('tx_openid_mode') == 'finish') {
			$requestURL = t3lib_div::_GP('tx_openid_location');
			$claimedIdentifier = t3lib_div::_GP('tx_openid_claimed');
		} else {
			$requestURL = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
			$claimedIdentifier = $this->loginData['uname'];
		}
		$returnURL .= 'tx_openid_location=' . rawurlencode($requestURL) . '&' .
						'tx_openid_mode=finish&' .
						'tx_openid_claimed=' . rawurlencode($claimedIdentifier) . '&' .
						'tx_openid_signature=' . $this->getSignature($claimedIdentifier);
		return t3lib_div::locationHeaderUrl($returnURL);
	}

	/**
	 * Signs claimed id.
	 *
	 * @return void
	 */
	protected function getSignature($claimedIdentifier) {
		// You can also increase security by using sha1 (beware of too long URLs!)
		return md5(implode('/', array(
			$claimedIdentifier,
			strval(strlen($claimedIdentifier)),
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
		)));
	}

	/**
	 * Calculates the path to the TYPO3 directory from the current directory
	 *
	 * @return string
	 */
	protected function getBackPath() {
		$extPath = t3lib_extMgm::siteRelPath('openid');
		$segmentCount = count(explode('/', $extPath));
		$path = str_pad('', $segmentCount*3, '../') . TYPO3_mainDir;

		return $path;
	}

	/**
	 * Obtains a real identifier for the user
	 *
	 * @return string
	 */
	protected function getFinalOpenIDIdentifier() {
		$result = $this->getSignedParameter('openid_identity');
		if (!$result) {
			$result = $this->getSignedParameter('openid_claimed_id');
		}
		if (!$result) {
			$result = $this->getSignedClaimedOpenIDIdentifier();
		}
		$result = $this->getAdjustedOpenIDIdentifier($result);
		return $result;
	}

	/**
	 * Gets the signed OpenID that was sent back to this service.
	 *
	 * @return string The signed OpenID, if signature did not match this is empty
	 */
	protected function getSignedClaimedOpenIDIdentifier() {
		$result = t3lib_div::_GP('tx_openid_claimed');
		$signature = $this->getSignature($result);
		if ($signature !== t3lib_div::_GP('tx_openid_signature')) {
			$result = '';
		}
		return $result;
	}

	/**
	 * Adjusts the OpenID identifier to to claimed OpenID, if the only difference
	 * is in normalizing the URLs. Example:
	 *	+ OpenID returned from provider: https://account.provider.net/
	 *	+ OpenID used in TYPO3: https://account.provider.net (not normalized)
	 *
	 * @param string $openIDIdentifier The OpenID returned by the OpenID provider
	 * @return string Adjusted OpenID identifier
	 */
	protected function getAdjustedOpenIDIdentifier($openIDIdentifier) {
		$result = '';

		$claimedOpenIDIdentifier = $this->getSignedClaimedOpenIDIdentifier();
		$pattern = '#^' . preg_quote($claimedOpenIDIdentifier, '#') . '/?$#';

		if (preg_match($pattern, $openIDIdentifier)) {
			$result = $claimedOpenIDIdentifier;
		}

		return $result;
	}

	/**
	 * Obtains a value of the parameter if it is signed. If not signed, then
	 * empty string is returned.
	 *
	 * @param string $parameterName Must start with 'openid_'
	 * @return string
	 */
	protected function getSignedParameter($parameterName) {
		$signedParametersList = t3lib_div::_GP('openid_signed');
		if (t3lib_div::inList($signedParametersList, substr($parameterName, 7))) {
			$result = t3lib_div::_GP($parameterName);
		} else {
			$result = '';
		}
		return $result;
	}

	/**
	 * Writes log message. Destination log depends on the current system mode.
	 * For FE the function writes to the admin panel log. For BE messages are
	 * sent to the system log. If developer log is enabled, messages are also
	 * sent there.
	 *
	 * This function accepts variable number of arguments and can format
	 * parameters. The syntax is the same as for sprintf()
	 *
	 * @param	string		$message	Message to output
	 * @return	void
	 * @see	sprintf()
	 * @see	t3lib::divLog()
	 * @see	t3lib_div::sysLog()
	 * @see	t3lib_timeTrack::setTSlogMessage()
	 */
	protected function writeLog($message) {
		if (func_num_args() > 1) {
			$params = func_get_args();
			array_shift($params);
			$message = vsprintf($message, $params);
		}
		if (TYPO3_MODE == 'BE') {
			t3lib_div::sysLog($message, $this->extKey, 1);
		} else {
			$GLOBALS['TT']->setTSlogMessage($message);
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
			t3lib_div::devLog($message, $this->extKey, 1);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/sv1/class.tx_openid_sv1.php']);
}

?>