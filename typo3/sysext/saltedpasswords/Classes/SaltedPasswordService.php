<?php
namespace TYPO3\CMS\Saltedpasswords;

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
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @author Steffen Ritter <info@rs-websystems.de>
 * @since 2009-06-14
 */
class SaltedPasswordService extends \TYPO3\CMS\Sv\AbstractAuthenticationService {

	/**
	 * Keeps class name.
	 *
	 * @var string
	 */
	public $prefixId = 'tx_saltedpasswords_sv1';

	/**
	 * Keeps path to this script relative to the extension directory.
	 *
	 * @var string
	 */
	public $scriptRelPath = 'sv1/class.tx_saltedpasswords_sv1.php';

	/**
	 * Keeps extension key.
	 *
	 * @var string
	 */
	public $extKey = 'saltedpasswords';

	/**
	 * Keeps extension configuration.
	 *
	 * @var mixed
	 */
	protected $extConf;

	/**
	 * An instance of the salted hashing method.
	 * This member is set in the getSaltingInstance() function.
	 *
	 * @var \TYPO3\CMS\Saltedpasswords\Salt\AbstractSalt
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
	 * @return boolean TRUE if service is available
	 */
	public function init() {
		$available = FALSE;
		$mode = TYPO3_MODE;
		if ($this->info['requestedServiceSubType'] === 'authUserBE') {
			$mode = 'BE';
		} elseif ($this->info['requestedServiceSubType'] === 'authUserFE') {
			$mode = 'FE';
		}
		if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled($mode)) {
			$available = TRUE;
			$this->extConf = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::returnExtConf();
		}
		return $available ? parent::init() : FALSE;
	}

	/**
	 * Checks the login data with the user record data for builtin login method.
	 *
	 * @param array $user User data array
	 * @param array $loginData Login data array
	 * @param string $security_level Login security level (optional)
	 * @return boolean TRUE if login data matched
	 * @todo Define visibility
	 */
	public function compareUident(array $user, array $loginData, $security_level = 'normal') {
		$validPasswd = FALSE;
		// Could be merged; still here to clarify
		if (!strcmp(TYPO3_MODE, 'BE')) {
			$password = $loginData['uident_text'];
		} elseif (!strcmp(TYPO3_MODE, 'FE')) {
			$password = $loginData['uident_text'];
		}
		// Determine method used for given salted hashed password
		$this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($user['password']);
		// Existing record is in format of Salted Hash password
		if (is_object($this->objInstanceSaltedPW)) {
			$validPasswd = $this->objInstanceSaltedPW->checkPassword($password, $user['password']);
			// Record is in format of Salted Hash password but authentication failed
			// skip further authentication methods
			if (!$validPasswd) {
				$this->authenticationFailed = TRUE;
			}
			$defaultHashingClassName = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::getDefaultSaltingHashingMethod();
			$skip = FALSE;
			// Test for wrong salted hashing method
			if ($validPasswd && !(get_class($this->objInstanceSaltedPW) == $defaultHashingClassName) || is_subclass_of($this->objInstanceSaltedPW, $defaultHashingClassName)) {
				// Instanciate default method class
				$this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL);
				$this->updatePassword(intval($user['uid']), array('password' => $this->objInstanceSaltedPW->getHashedPassword($password)));
			}
			if ($validPasswd && !$skip && $this->objInstanceSaltedPW->isHashUpdateNeeded($user['password'])) {
				$this->updatePassword(intval($user['uid']), array('password' => $this->objInstanceSaltedPW->getHashedPassword($password)));
			}
		} elseif (!intval($this->extConf['forceSalted'])) {
			// Stored password is in deprecated salted hashing method
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('C$,M$', substr($user['password'], 0, 2))) {
				// Instanciate default method class
				$this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(substr($user['password'], 1));
				// md5
				if (!strcmp(substr($user['password'], 0, 1), 'M')) {
					$validPasswd = $this->objInstanceSaltedPW->checkPassword(md5($password), substr($user['password'], 1));
				} else {
					$validPasswd = $this->objInstanceSaltedPW->checkPassword($password, substr($user['password'], 1));
				}
				// Skip further authentication methods
				if (!$validPasswd) {
					$this->authenticationFailed = TRUE;
				}
			} elseif (preg_match('/[0-9abcdef]{32,32}/', $user['password'])) {
				$validPasswd = !strcmp(md5($password), $user['password']) ? TRUE : FALSE;
				// Skip further authentication methods
				if (!$validPasswd) {
					$this->authenticationFailed = TRUE;
				}
			} else {
				$validPasswd = !strcmp($password, $user['password']) ? TRUE : FALSE;
			}
			// Should we store the new format value in DB?
			if ($validPasswd && intval($this->extConf['updatePasswd'])) {
				// Instanciate default method class
				$this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL);
				$this->updatePassword(intval($user['uid']), array('password' => $this->objInstanceSaltedPW->getHashedPassword($password)));
			}
		}
		return $validPasswd;
	}

	/**
	 * Method adds a further authUser method.
	 *
	 * Will return one of following authentication status codes:
	 * - 0 - authentication failure
	 * - 100 - just go on. User is not authenticated but there is still no reason to stop
	 * - 200 - the service was able to authenticate the user
	 *
	 * @param array Array containing FE user data of the logged user.
	 * @return integer Authentication statuscode, one of 0,100 and 200
	 */
	public function authUser(array $user) {
		$OK = 100;
		$validPasswd = FALSE;
		if ($this->login['uident'] && $this->login['uname']) {
			if (!empty($this->login['uident_text'])) {
				$validPasswd = $this->compareUident($user, $this->login);
			}
			if (!$validPasswd) {
				// Failed login attempt (wrong password)
				$errorMessage = 'Login-attempt from %s (%s), username \'%s\', password not accepted!';
				// No delegation to further services
				if (intval($this->extConf['onlyAuthService']) || $this->authenticationFailed) {
					$this->writeLogMessage(TYPO3_MODE . ' Authentication failed - wrong password for username \'%s\'', $this->login['uname']);
				} else {
					$this->writeLogMessage($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']);
				}
				$this->writelog(255, 3, 3, 1, $errorMessage, array(
					$this->authInfo['REMOTE_ADDR'],
					$this->authInfo['REMOTE_HOST'],
					$this->login['uname']
				));
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO);
				if (intval($this->extConf['onlyAuthService']) || $this->authenticationFailed) {
					$OK = 0;
				}
			} elseif ($validPasswd && $user['lockToDomain'] && strcasecmp($user['lockToDomain'], $this->authInfo['HTTP_HOST'])) {
				// Lock domain didn't match, so error:
				$errorMessage = 'Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!';
				$this->writeLogMessage($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'], $user['lockToDomain'], $this->authInfo['HTTP_HOST']);
				$this->writelog(255, 3, 3, 1, $errorMessage, array(
					$this->authInfo['REMOTE_ADDR'],
					$this->authInfo['REMOTE_HOST'],
					$user[$this->db_user['username_column']],
					$user['lockToDomain'],
					$this->authInfo['HTTP_HOST']
				));
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO);
				$OK = 0;
			} elseif ($validPasswd) {
				$this->writeLogMessage(TYPO3_MODE . ' Authentication successful for username \'%s\'', $this->login['uname']);
				$OK = 200;
			}
		}
		return $OK;
	}

	/**
	 * Method updates a FE/BE user record - in this case a new password string will be set.
	 *
	 * @param integer $uid uid of user record that will be updated
	 * @param mixed $updateFields Field values as key=>value pairs to be updated in database
	 * @return void
	 */
	protected function updatePassword($uid, $updateFields) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->pObj->user_table, sprintf('uid = %u', $uid), $updateFields);
		\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(sprintf('Automatic password update for user record in %s with uid %u', $this->pObj->user_table, $uid), $this->extKey, 1);
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
	 * @return void
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog()
	 * @todo Define visibility
	 */
	public function writeLogMessage($message) {
		if (func_num_args() > 1) {
			$params = func_get_args();
			array_shift($params);
			$message = vsprintf($message, $params);
		}
		if (TYPO3_MODE === 'BE') {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE);
		} else {
			$GLOBALS['TT']->setTSlogMessage($message);
		}
		if (TYPO3_DLOG) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE);
		}
	}

}


?>