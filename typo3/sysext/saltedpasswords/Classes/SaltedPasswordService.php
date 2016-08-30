<?php
namespace TYPO3\CMS\Saltedpasswords;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class implements salted-password hashes authentication service.
 * Contains authentication service class for salted hashed passwords.
 * @since 2009-06-14
 */
class SaltedPasswordService extends \TYPO3\CMS\Sv\AbstractAuthenticationService
{
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
    protected $objInstanceSaltedPW = null;

    /**
     * Indicates whether the salted password authentication has failed.
     *
     * Prevents authentication bypass. See vulnerability report:
     * { @link http://forge.typo3.org/issues/22030 }
     *
     * @var bool
     */
    protected $authenticationFailed = false;

    /**
     * Checks if service is available. In case of this service we check that
     * following prerequesties are fulfilled:
     * - loginSecurityLevel of according TYPO3_MODE is set to normal
     *
     * @return bool TRUE if service is available
     */
    public function init()
    {
        $available = false;
        $mode = TYPO3_MODE;
        if ($this->info['requestedServiceSubType'] === 'authUserBE') {
            $mode = 'BE';
        } elseif ($this->info['requestedServiceSubType'] === 'authUserFE') {
            $mode = 'FE';
        }
        if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled($mode)) {
            $available = true;
            $this->extConf = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::returnExtConf();
        }
        return $available ? parent::init() : false;
    }

    /**
     * Checks the login data with the user record data for builtin login method.
     *
     * @param array $user User data array
     * @param array $loginData Login data array
     * @param string $passwordCompareStrategy Password compare strategy
     * @return bool TRUE if login data matched
     */
    public function compareUident(array $user, array $loginData, $passwordCompareStrategy = '')
    {
        $validPasswd = false;
        $password = $loginData['uident_text'];
        // Determine method used for given salted hashed password
        $this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($user['password']);
        // Existing record is in format of Salted Hash password
        if (is_object($this->objInstanceSaltedPW)) {
            $validPasswd = $this->objInstanceSaltedPW->checkPassword($password, $user['password']);
            // Record is in format of Salted Hash password but authentication failed
            // skip further authentication methods
            if (!$validPasswd) {
                $this->authenticationFailed = true;
            }
            $defaultHashingClassName = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::getDefaultSaltingHashingMethod();
            $skip = false;
            // Test for wrong salted hashing method
            if ($validPasswd && !(get_class($this->objInstanceSaltedPW) == $defaultHashingClassName) || is_subclass_of($this->objInstanceSaltedPW, $defaultHashingClassName)) {
                // Instantiate default method class
                $this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null);
                $this->updatePassword((int)$user['uid'], ['password' => $this->objInstanceSaltedPW->getHashedPassword($password)]);
            }
            if ($validPasswd && !$skip && $this->objInstanceSaltedPW->isHashUpdateNeeded($user['password'])) {
                $this->updatePassword((int)$user['uid'], ['password' => $this->objInstanceSaltedPW->getHashedPassword($password)]);
            }
        } elseif (!(int)$this->extConf['forceSalted']) {
            // Stored password is in deprecated salted hashing method
            $hashingMethod = substr($user['password'], 0, 2);
            if ($hashingMethod === 'C$' || $hashingMethod === 'M$') {
                // Instantiate default method class
                $this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(substr($user['password'], 1));
                // md5
                if ($hashingMethod === 'M$') {
                    $validPasswd = $this->objInstanceSaltedPW->checkPassword(md5($password), substr($user['password'], 1));
                } else {
                    $validPasswd = $this->objInstanceSaltedPW->checkPassword($password, substr($user['password'], 1));
                }
                // Skip further authentication methods
                if (!$validPasswd) {
                    $this->authenticationFailed = true;
                }
            } elseif (preg_match('/[0-9abcdef]{32,32}/', $user['password'])) {
                $validPasswd = md5($password) === (string)$user['password'];
                // Skip further authentication methods
                if (!$validPasswd) {
                    $this->authenticationFailed = true;
                }
            } else {
                $validPasswd = (string)$password !== '' && (string)$password === (string)$user['password'];
            }
            // Should we store the new format value in DB?
            if ($validPasswd && (int)$this->extConf['updatePasswd']) {
                // Instantiate default method class
                $this->objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null);
                $this->updatePassword((int)$user['uid'], ['password' => $this->objInstanceSaltedPW->getHashedPassword($password)]);
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
     * @return int Authentication statuscode, one of 0,100 and 200
     */
    public function authUser(array $user)
    {
        $OK = 100;
        // The salted password service can only work correctly, if a non empty username along with a non empty password is provided.
        // Otherwise a different service is allowed to check for other login credentials
        if ((string)$this->login['uident_text'] !== '' && (string)$this->login['uname'] !== '') {
            $validPasswd = $this->compareUident($user, $this->login);
            if (!$validPasswd) {
                // Failed login attempt (wrong password)
                $errorMessage = 'Login-attempt from %s (%s), username \'%s\', password not accepted!';
                // No delegation to further services
                if ((int)$this->extConf['onlyAuthService'] || $this->authenticationFailed) {
                    $this->writeLogMessage(TYPO3_MODE . ' Authentication failed - wrong password for username \'%s\'', $this->login['uname']);
                    $OK = 0;
                } else {
                    $this->writeLogMessage($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']);
                }
                $this->writelog(255, 3, 3, 1, $errorMessage, [
                    $this->authInfo['REMOTE_ADDR'],
                    $this->authInfo['REMOTE_HOST'],
                    $this->login['uname']
                ]);
                \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO);
            } elseif ($validPasswd && $user['lockToDomain'] && strcasecmp($user['lockToDomain'], $this->authInfo['HTTP_HOST'])) {
                // Lock domain didn't match, so error:
                $errorMessage = 'Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!';
                $this->writeLogMessage($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'], $user['lockToDomain'], $this->authInfo['HTTP_HOST']);
                $this->writelog(255, 3, 3, 1, $errorMessage, [
                    $this->authInfo['REMOTE_ADDR'],
                    $this->authInfo['REMOTE_HOST'],
                    $user[$this->db_user['username_column']],
                    $user['lockToDomain'],
                    $this->authInfo['HTTP_HOST']
                ]);
                \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf($errorMessage, $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']), 'core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO);
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
     * @param int $uid uid of user record that will be updated
     * @param mixed $updateFields Field values as key=>value pairs to be updated in database
     * @return void
     */
    protected function updatePassword($uid, $updateFields)
    {
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
     */
    public function writeLogMessage($message)
    {
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
