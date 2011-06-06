<?php
/***************************************************************
*  Copyright notice
*
*  (c) Marcus Krause (marcus#exp2009@t3sec.info)
*  (c) Steffen Ritter (info@rs-websystems.de)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Contains authentication service class for salted hashed passwords.
 */


/**
 * Class implements salted-password hashes authentication service.
 *
 * @author	Marcus Krause <marcus#exp2009@t3sec.info>
 * @author	Steffen Ritter <info@rs-websystems.de>
 *
 * @since	2009-06-14
 * @package	TYPO3
 * @subpackage	tx_saltedpasswords
 */
class tx_saltedpasswords_sv1 extends tx_sv_authbase {
	/**
	 * Keeps class name.
	 *
	 * @var	string
	 */
	public $prefixId = 'tx_saltedpasswords_sv1';

	/**
	 * Keeps path to this script relative to the extension directory.
	 *
	 * @var	string
	 */
	public $scriptRelPath = 'sv1/class.tx_saltedpasswords_sv1.php';

	/**
	 * Keeps extension key.
	 *
	 * @var	string
	 */
	public $extKey = 'saltedpasswords';

	/**
	 * Keeps extension configuration.
	 *
	 * @var	mixed
	 */
	protected $extConf;

	/**
	 * An instance of the salted hashing method.
	 * This member is set in the getSaltingInstance() function.
	 *
	 * @var	tx_saltedpasswords_abstract_salts
	 */
	protected $objInstanceSaltedPW = NULL;

	/**
	 * Indicates whether the salted password authentication has failed.
	 *
	 * Prevents authentication bypass. See vulnerability report:
	 * { @link http://bugs.typo3.org/view.php?id=13372 }
	 *
	 * @var boolean
	 */
	protected $authenticationFailed = FALSE;

	/**
	 * Checks if service is available. In case of this service we check that
	 * following prerequesties are fulfilled:
	 * - loginSecurityLevel of according TYPO3_MODE is set to normal
	 *
	 * @return	boolean		TRUE if service is available
	 */
	public function init() {
		$available = FALSE;

		if (tx_saltedpasswords_div::isUsageEnabled()) {
			$available = TRUE;
			$this->extConf = tx_saltedpasswords_div::returnExtConf();
		}

		return $available ? parent::init() : FALSE;
	}

	/**
	 * Checks the login data with the user record data for builtin login method.
	 *
	 * @param	array		user data array
	 * @param	array		login data array
	 * @param	string		login security level (optional)
	 * @return	boolean		TRUE if login data matched
	 */
	function compareUident(array $user, array $loginData, $security_level = 'normal') {
		$validPasswd = FALSE;

			// could be merged; still here to clarify
		if (!strcmp(TYPO3_MODE, 'BE')) {
			$password = $loginData['uident_text'];
		} elseif (!strcmp(TYPO3_MODE, 'FE')) {
			$password = $loginData['uident_text'];
		}

			// determine method used for given salted hashed password
		$this->objInstanceSaltedPW = tx_saltedpasswords_salts_factory::getSaltingInstance($user['password']);

			// existing record is in format of Salted Hash password
		if (is_object($this->objInstanceSaltedPW)) {
			$validPasswd = $this->objInstanceSaltedPW->checkPassword($password,$user['password']);

				// record is in format of Salted Hash password but authentication failed
				// skip further authentication methods
			if (!$validPasswd) {
				$this->authenticationFailed = TRUE;
			}

			$defaultHashingClassName = tx_saltedpasswords_div::getDefaultSaltingHashingMethod();
			$skip = FALSE;

				// test for wrong salted hashing method
			if ($validPasswd && !(get_class($this->objInstanceSaltedPW) == $defaultHashingClassName) || (is_subclass_of($this->objInstanceSaltedPW, $defaultHashingClassName))) {
					// instanciate default method class
				$this->objInstanceSaltedPW = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);
				$this->updatePassword(
					intval($user['uid']),
					array('password' => $this->objInstanceSaltedPW->getHashedPassword($password))
				);
			}

			if ($validPasswd && !$skip && $this->objInstanceSaltedPW->isHashUpdateNeeded($user['password'])) {
				$this->updatePassword(
					intval($user['uid']),
					array('password' => $this->objInstanceSaltedPW->getHashedPassword($password))
				);
			}
			// we process also clear-text, md5 and passwords updated by Portable PHP password hashing framework
		} elseif (!intval($this->extConf['forceSalted'])) {

				// stored password is in deprecated salted hashing method
			if (t3lib_div::inList('C$,M$', substr($user['password'], 0, 2))) {

					// instanciate default method class
				$this->objInstanceSaltedPW = tx_saltedpasswords_salts_factory::getSaltingInstance(substr($user['password'], 1));

					// md5
				if (!strcmp(substr($user['password'], 0, 1), 'M')) {
					$validPasswd = $this->objInstanceSaltedPW->checkPassword(md5($password), substr($user['password'], 1));
				} else {
					$validPasswd = $this->objInstanceSaltedPW->checkPassword($password, substr($user['password'], 1));
				}

					// skip further authentication methods
				if (!$validPasswd) {
					$this->authenticationFailed = TRUE;
				}

				// password is stored as md5
			} elseif (preg_match('/[0-9abcdef]{32,32}/', $user['password'])) {
				$validPasswd = (!strcmp(md5($password), $user['password']) ? TRUE : FALSE);

					// skip further authentication methods
				if (!$validPasswd) {
					$this->authenticationFailed = TRUE;
				}

				// password is stored plain or unrecognized format
			} else {
				$validPasswd = (!strcmp($password, $user['password']) ? TRUE : FALSE);
			}
				// should we store the new format value in DB?
			if ($validPasswd && intval($this->extConf['updatePasswd'])) {
					// instanciate default method class
				$this->objInstanceSaltedPW = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);
				$this->updatePassword(
					intval($user['uid']),
					array('password' => $this->objInstanceSaltedPW->getHashedPassword($password))
				);
			}
		}

		return $validPasswd;
	}

	/**
	 * Method adds a further authUser method.
	 *
	 * Will return one of following authentication status codes:
	 *  - 0 - authentication failure
	 *  - 100 - just go on. User is not authenticated but there is still no reason to stop
	 *  - 200 - the service was able to authenticate the user
	 *
	 * @param	array		Array containing FE user data of the logged user.
	 * @return	integer		authentication statuscode, one of 0,100 and 200
	 */
	public function authUser(array $user) {
		$OK = 100;
		$validPasswd = FALSE;

		if ($this->pObj->security_level == 'rsa' && t3lib_extMgm::isLoaded('rsaauth')) {
			require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
			require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/storage/class.tx_rsaauth_storagefactory.php');

			$backend = tx_rsaauth_backendfactory::getBackend();
			$storage = tx_rsaauth_storagefactory::getStorage();
				// Preprocess the password
			$password = $this->login['uident'];
			$key = $storage->get();
			if ($key != NULL && substr($password, 0, 4) == 'rsa:') {
				// Decode password and pass to parent
				$decryptedPassword = $backend->decrypt($key, substr($password, 4));
				$this->login['uident_text'] = $decryptedPassword;
			}
		}

		if ($this->login['uident'] && $this->login['uname']) {
			if (!empty($this->login['uident_text'])) {
				$validPasswd = $this->compareUident(
					$user,
					$this->login
				);
			}

			if (!$validPasswd) {
					// Failed login attempt (wrong password)
				$errorMessage = 'Login-attempt from %s (%s), username \'%s\', password not accepted!';
					// no delegation to further services
				if (intval($this->extConf['onlyAuthService']) || $this->authenticationFailed) {
					$this->writeLogMessage(
						TYPO3_MODE . ' Authentication failed - wrong password for username \'%s\'',
						$this->login['uname']
					);
				} else {
					$this->writeLogMessage(
						$errorMessage,
						$this->authInfo['REMOTE_ADDR'],
						$this->authInfo['REMOTE_HOST'],
						$this->login['uname']
					);
				}
				$this->writelog(255, 3, 3, 1,
					$errorMessage,
					array(
						$this->authInfo['REMOTE_ADDR'],
						$this->authInfo['REMOTE_HOST'],
						$this->login['uname']
					)
				);
				t3lib_div::sysLog(
					sprintf(
						$errorMessage,
						$this->authInfo['REMOTE_ADDR'],
						$this->authInfo['REMOTE_HOST'],
						$this->login['uname']
					),
					'Core',
					0
				);
				if (intval($this->extConf['onlyAuthService']) || $this->authenticationFailed) {
					$OK = 0;
				}
			} elseif ($validPasswd && $user['lockToDomain'] && strcasecmp($user['lockToDomain'], $this->authInfo['HTTP_HOST'])) {
					// Lock domain didn't match, so error:
				$errorMessage = 'Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!';
				$this->writeLogMessage(
					$errorMessage,
					$this->authInfo['REMOTE_ADDR'],
					$this->authInfo['REMOTE_HOST'],
					$this->login['uname'],
					$user['lockToDomain'],
					$this->authInfo['HTTP_HOST']
				);
				$this->writelog(255, 3, 3, 1,
					$errorMessage,
					array(
						$this->authInfo['REMOTE_ADDR'],
						$this->authInfo['REMOTE_HOST'],
						$user[$this->db_user['username_column']],
						$user['lockToDomain'],
						$this->authInfo['HTTP_HOST']
					)
				);
				t3lib_div::sysLog(
					sprintf(
						$errorMessage,
						$this->authInfo['REMOTE_ADDR'],
						$this->authInfo['REMOTE_HOST'],
						$user[$this->db_user['username_column']],
						$user['lockToDomain'],
						$this->authInfo['HTTP_HOST']
					),
					'Core',
					0
				);
				$OK = 0;
			} elseif ($validPasswd) {
				$this->writeLogMessage(
					TYPO3_MODE . ' Authentication successful for username \'%s\'',
					$this->login['uname']
				);
				$OK = 200;
			}
		}

		return $OK;
	}

	/**
	 * Method updates a FE/BE user record - in this case a new password string will be set.
	 *
	 * @param	integer		$uid: uid of user record that will be updated
	 * @param	mixed		$updateFields: Field values as key=>value pairs to be updated in database
	 * @return	void
	 */
	protected function updatePassword($uid, $updateFields) {
		if (TYPO3_MODE === 'BE') {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery( 'be_users', sprintf('uid = %u', $uid), $updateFields);
		} else {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery( 'fe_users', sprintf('uid = %u', $uid), $updateFields);
		}

		t3lib_div::devLog(sprintf('Automatic password update for %s user with uid %u', TYPO3_MODE, $uid), $this->extKey, 1);
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
	 * @param	string		$message: Message to output
	 * @return	void
	 * @see	sprintf()
	 * @see	t3lib::divLog()
	 * @see	t3lib_div::sysLog()
	 * @see	t3lib_timeTrack::setTSlogMessage()
	 */
	function writeLogMessage($message) {
		if (func_num_args() > 1) {
			$params = func_get_args();
			array_shift($params);
			$message = vsprintf($message, $params);
		}

		if (TYPO3_MODE === 'BE') {
			t3lib_div::sysLog($message, $this->extKey, 1);
		} else {
			$GLOBALS['TT']->setTSlogMessage($message);
		}

		if (TYPO3_DLOG) {
			t3lib_div::devLog($message, $this->extKey, 1);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/saltedpasswords/sv1/class.tx_saltedpasswords_sv1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/saltedpasswords/sv1/class.tx_saltedpasswords_sv1.php']);
}
?>