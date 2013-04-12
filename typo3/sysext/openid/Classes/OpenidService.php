<?php
namespace TYPO3\CMS\Openid;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Dmitry Dulepov <dmitry@typo3.org>
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

require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('openid') . 'lib/php-openid/Auth/OpenID/Interface.php';

/**
 * Service "OpenID Authentication" for the "openid" extension.
 *
 * @author 	Dmitry Dulepov <dmitry@typo3.org>
 */
class OpenidService extends \TYPO3\CMS\Core\Service\AbstractService {

	/**
	 * Class name
	 */
	public $prefixId = 'tx_openid_sv1';

	// Same as class name
	/**
	 * Path to this script relative to the extension directory
	 */
	public $scriptRelPath = 'sv1/class.tx_openid_sv1.php';

	/**
	 * The extension key
	 */
	public $extKey = 'openid';

	/**
	 * Login data as passed to initAuth()
	 */
	protected $loginData = array();

	/**
	 * Additional authentication information provided by \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication.
	 * We use it to decide what database table contains user records.
	 */
	protected $authenticationInformation = array();

	/**
	 * OpenID identifier after it has been normalized.
	 */
	protected $openIDIdentifier;

	/**
	 * OpenID response object. It is initialized when OpenID provider returns
	 * with success/failure response to us.
	 *
	 * @var \Auth_OpenID_ConsumerResponse
	 */
	protected $openIDResponse = NULL;

	/**
	 * A reference to the calling object
	 *
	 * @var \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
	 */
	protected $parentObject;

	/**
	 * If set to TRUE, than libraries are already included.
	 */
	static protected $openIDLibrariesIncluded = FALSE;

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
	 * @return boolean TRUE if service is available
	 */
	public function init() {
		$available = FALSE;
		if (extension_loaded('gmp')) {
			$available = is_callable('gmp_init');
		} elseif (extension_loaded('bcmath')) {
			$available = is_callable('bcadd');
		} else {
			$this->writeLog('Neither bcmath, nor gmp PHP extension found. OpenID authentication will not be available.');
		}
		// We also need set_include_path() PHP function
		if (!is_callable('set_include_path')) {
			$available = FALSE;
			$this->writeLog('set_include_path() PHP function is not available. OpenID authentication is disabled.');
		}
		return $available ? parent::init() : FALSE;
	}

	/**
	 * Initializes authentication for this service.
	 *
	 * @param string $subType: Subtype for authentication (either "getUserFE" or "getUserBE")
	 * @param array $loginData: Login data submitted by user and preprocessed by AbstractUserAuthentication
	 * @param array $authenticationInformation: Additional TYPO3 information for authentication services (unused here)
	 * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $parentObject Calling object
	 * @return void
	 */
	public function initAuth($subType, array $loginData, array $authenticationInformation, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication &$parentObject) {
		// Store login and authetication data
		$this->loginData = $loginData;
		$this->authenticationInformation = $authenticationInformation;
		// Implement normalization according to OpenID 2.0 specification
		$this->openIDIdentifier = $this->normalizeOpenID($this->loginData['uname']);
		// If we are here after authentication by the OpenID server, get its response.
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_mode') == 'finish' && $this->openIDResponse == NULL) {
			$this->includePHPOpenIDLibrary();
			$openIDConsumer = $this->getOpenIDConsumer();
			$this->openIDResponse = $openIDConsumer->complete($this->getReturnURL());
		}
		$this->parentObject = $parentObject;
	}

	/**
	 * This function returns the user record back to the \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication.
	 * It does not mean that user is authenticated, it means only that user is found. This
	 * function makes sure that user cannot be authenticated by any other service
	 * if user tries to use OpenID to authenticate.
	 *
	 * @return mixed User record (content of fe_users/be_users as appropriate for the current mode)
	 */
	public function getUser() {
		$userRecord = NULL;
		if ($this->loginData['status'] == 'login') {
			if ($this->openIDResponse instanceof \Auth_OpenID_ConsumerResponse) {
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
						if ($userRecord != NULL) {
							$this->writeLog('User \'%s\' logged in with OpenID \'%s\'', $userRecord[$this->parentObject->formfield_uname], $openIDIdentifier);
						} else {
							$this->writeLog('Failed to login user using OpenID \'%s\'', $openIDIdentifier);
						}
					}
				}
			} else {
				// Here if user just started authentication
				$userRecord = $this->getUserRecord($this->openIDIdentifier);
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
	 * @param array $userRecord	User record
	 * @return integer Code that shows if user is really authenticated.
	 */
	public function authUser(array $userRecord) {
		$result = 100;
		// 100 means "we do not know, continue"
		if ($userRecord['tx_openid_openid'] !== '') {
			// Check if user is identified by the OpenID
			if ($this->openIDResponse instanceof \Auth_OpenID_ConsumerResponse) {
				// If we have a response, it means OpenID server tried to authenticate
				// the user. Now we just look what is the status and provide
				// corresponding response to the caller
				if ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
					// Success (code 200)
					$result = 200;
				} else {
					$this->writeLog('OpenID authentication failed with code \'%s\'.', $this->openIDResponse->status);
				}
			} else {
				// We may need to send a request to the OpenID server.
				// First, check if the supplied login name equals with the configured OpenID.
				if ($this->openIDIdentifier == $userRecord['tx_openid_openid']) {
					// Next, check if the user identifier looks like an OpenID identifier.
					// Prevent PHP warning in case if identifiers is not an OpenID identifier
					// (not an URL).
					// TODO: Improve testing here. After normalization has been added, now all identifiers will succeed here...
					$urlParts = @parse_url($this->openIDIdentifier);
					if (is_array($urlParts) && $urlParts['scheme'] != '' && $urlParts['host']) {
						// Yes, this looks like a good OpenID. Ask OpenID server (should not return)
						$this->sendOpenIDRequest();
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Includes necessary files for the PHP OpenID library
	 *
	 * @return void
	 */
	protected function includePHPOpenIDLibrary() {
		if (!self::$openIDLibrariesIncluded) {
			// Prevent further calls
			self::$openIDLibrariesIncluded = TRUE;
			// PHP OpenID libraries requires adjustments of path settings
			$oldIncludePath = get_include_path();
			$phpOpenIDLibPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('openid') . 'lib/php-openid';
			@set_include_path(($phpOpenIDLibPath . PATH_SEPARATOR . $phpOpenIDLibPath . PATH_SEPARATOR . 'Auth' . PATH_SEPARATOR . $oldIncludePath));
			// Make sure that random generator is properly set up. Constant could be
			// defined by the previous inclusion of the file
			if (!defined('Auth_OpenID_RAND_SOURCE')) {
				if (TYPO3_OS == 'WIN') {
					// No random generator on Windows!
					define('Auth_OpenID_RAND_SOURCE', NULL);
				} elseif (!is_readable('/dev/urandom')) {
					if (is_readable('/dev/random')) {
						define('Auth_OpenID_RAND_SOURCE', '/dev/random');
					} else {
						define('Auth_OpenID_RAND_SOURCE', NULL);
					}
				}
			}
			// Include files
			require_once $phpOpenIDLibPath . '/Auth/OpenID/Consumer.php';
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
	 * @param string $openIDIdentifier	OpenID identifier to search for
	 * @return array Database fields from the table that corresponds to the current login mode (FE/BE)
	 */
	protected function getUserRecord($openIDIdentifier) {
		$record = NULL;
		if ($openIDIdentifier) {
			// $openIDIdentifier always as a trailing slash because it got normalized
			// but tx_openid_openid possibly not so check for both alternatives in database
			$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->authenticationInformation['db_user']['table'], 'tx_openid_openid IN (' . $GLOBALS['TYPO3_DB']->fullQuoteStr($openIDIdentifier, $this->authenticationInformation['db_user']['table']) . ',' . $GLOBALS['TYPO3_DB']->fullQuoteStr(rtrim($openIDIdentifier, '/'), $this->authenticationInformation['db_user']['table']) . ')' . $this->authenticationInformation['db_user']['check_pid_clause'] . $this->authenticationInformation['db_user']['enable_clause']);
			if ($record) {
				// Make sure to work only with normalized OpenID during the whole process
				$record['tx_openid_openid'] = $this->normalizeOpenID($record['tx_openid_openid']);
			}
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
	 *
	 * @todo use DB (or the caching framework) instead of the filesystem to store OpenID data
	 * @return Auth_OpenID_Consumer Consumer instance
	 */
	protected function getOpenIDConsumer() {
		$openIDStore = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Openid\\OpenidStore');
		/* @var $openIDStore tx_openid_store */
		$openIDStore->cleanup();
		return new \Auth_OpenID_Consumer($openIDStore);
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
	 * @return void
	 */
	protected function sendOpenIDRequest() {
		$this->includePHPOpenIDLibrary();
		$openIDIdentifier = $this->openIDIdentifier;
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
		$trustedRoot = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		if ($authenticationRequest->shouldSendRedirect()) {
			$redirectURL = $authenticationRequest->redirectURL($trustedRoot, $returnURL);
			// If the redirect URL can't be built, return. We can only return.
			if (\Auth_OpenID::isFailure($redirectURL)) {
				$this->writeLog('Authentication request could not create redirect URL for OpenID identifier \'%s\'', $openIDIdentifier);
				return;
			}
			// Send redirect. We use 303 code because it allows to redirect POST
			// requests without resending the form. This is exactly what we need here.
			// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.4
			@ob_end_clean();
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectURL, \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303);
		} else {
			$formHtml = $authenticationRequest->htmlMarkup($trustedRoot, $returnURL, FALSE, array('id' => 'openid_message'));
			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (\Auth_OpenID::isFailure($formHtml)) {
				// Form markup cannot be generated
				$this->writeLog('Could not create form markup for OpenID identifier \'%s\'', $openIDIdentifier);
				return;
			} else {
				@ob_end_clean();
				echo $formHtml;
			}
		}
		// If we reached this point, we must not return!
		die;
	}

	/**
	 * Creates return URL for the OpenID server. When a user is authenticated by
	 * the OpenID server, the user will be sent to this URL to complete
	 * authentication process with the current site. We send it to our script.
	 *
	 * @return string Return URL
	 */
	protected function getReturnURL() {
		if ($this->authenticationInformation['loginType'] == 'FE') {
			// We will use eID to send user back, create session data and
			// return to the calling page.
			// Notice: 'pid' and 'logintype' parameter names cannot be changed!
			// They are essential for FE user authentication.
			$returnURL = 'index.php?eID=tx_openid&' . 'pid=' . $this->authenticationInformation['db_user']['checkPidList'] . '&' . 'logintype=login&';
		} else {
			// In the Backend we will use dedicated script to create session.
			// It is much easier for the Backend to manage users.
			// Notice: 'login_status' parameter name cannot be changed!
			// It is essential for BE user authentication.
			$absoluteSiteURL = substr(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST')));
			$returnURL = $absoluteSiteURL . TYPO3_mainDir . 'sysext/' . $this->extKey . '/class.tx_openid_return.php?login_status=login&';
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_mode') == 'finish') {
			$requestURL = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_location');
			$claimedIdentifier = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_claimed');
		} else {
			$requestURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
			$claimedIdentifier = $this->openIDIdentifier;
		}
		$returnURL .= 'tx_openid_location=' . rawurlencode($requestURL) . '&' . 'tx_openid_mode=finish&' . 'tx_openid_claimed=' . rawurlencode($claimedIdentifier) . '&' . 'tx_openid_signature=' . $this->getSignature($claimedIdentifier);
		return \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($returnURL);
	}

	/**
	 * Signs claimed id.
	 *
	 * @param string $claimedIdentifier
	 * @return string
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
	 * Implement normalization according to OpenID 2.0 specification
	 * See http://openid.net/specs/openid-authentication-2_0.html#normalization
	 *
	 * @param string $openIDIdentifier OpenID identifier to normalize
	 * @return string Normalized OpenID identifier
	 */
	protected function normalizeOpenID($openIDIdentifier) {
		// Strip everything with and behind the fragment delimiter character "#"
		if (strpos($openIDIdentifier, '#') !== FALSE) {
			$openIDIdentifier = preg_replace('/#.*$/', '', $openIDIdentifier);
		}
		// A URI with a missing scheme is normalized to a http URI
		if (!preg_match('#^https?://#', $openIDIdentifier)) {
			$escapedIdentifier = $GLOBALS['TYPO3_DB']->quoteStr($openIDIdentifier, $this->authenticationInformation['db_user']['table']);
			$condition = 'tx_openid_openid IN (' . '\'http://' . $escapedIdentifier . '\',' . '\'http://' . $escapedIdentifier . '/\',' . '\'https://' . $escapedIdentifier . '\',' . '\'https://' . $escapedIdentifier . '/\'' . ')';
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('tx_openid_openid', $this->authenticationInformation['db_user']['table'], $condition);
			if (is_array($row)) {
				$openIDIdentifier = $row['tx_openid_openid'];
			}
		}
		// An empty path component is normalized to a slash
		// (e.g. "http://domain.org" -> "http://domain.org/")
		if (preg_match('#^https?://[^/]+$#', $openIDIdentifier)) {
			$openIDIdentifier .= '/';
		}
		return $openIDIdentifier;
	}

	/**
	 * Calculates the path to the TYPO3 directory from the current directory
	 *
	 * @return string
	 */
	protected function getBackPath() {
		$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('openid');
		$segmentCount = count(explode('/', $extPath));
		$path = str_pad('', $segmentCount * 3, '../') . TYPO3_mainDir;
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
		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_claimed');
		$signature = $this->getSignature($result);
		if ($signature !== \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_signature')) {
			$result = '';
		}
		return $result;
	}

	/**
	 * Adjusts the OpenID identifier to to claimed OpenID, if the only difference
	 * is in normalizing the URLs. Example:
	 * + OpenID returned from provider: https://account.provider.net/
	 * + OpenID used in TYPO3: https://account.provider.net (not normalized)
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
		$signedParametersList = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('openid_signed');
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($signedParametersList, substr($parameterName, 7))) {
			$result = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($parameterName);
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
	 * @param string $message Message to output
	 * @return 	void
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog()
	 * @see \TYPO3\CMS\Core\TimeTracker\TimeTracker::setTSlogMessage()
	 */
	protected function writeLog($message) {
		if (func_num_args() > 1) {
			$params = func_get_args();
			array_shift($params);
			$message = vsprintf($message, $params);
		}
		if (TYPO3_MODE == 'BE') {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE);
		} else {
			$GLOBALS['TT']->setTSlogMessage($message);
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE);
		}
	}

}

?>