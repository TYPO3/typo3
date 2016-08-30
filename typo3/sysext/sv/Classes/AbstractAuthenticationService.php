<?php
namespace TYPO3\CMS\Sv;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Service\AbstractService;

/**
 * Authentication services class
 */
class AbstractAuthenticationService extends AbstractService
{
    /**
     * User object
     *
     * @var AbstractUserAuthentication
     */
    public $pObj;

    /**
     * Subtype of the service which is used to call the service.
     *
     * @var string
     */
    public $mode;

    /**
     * Submitted login form data
     *
     * @var array
     */
    public $login = [];

    /**
     * Various data
     *
     * @var array
     */
    public $authInfo = [];

    /**
     * User db table definition
     *
     * @var array
     */
    public $db_user = [];

    /**
     * Usergroups db table definition
     *
     * @var array
     */
    public $db_groups = [];

    /**
     * If the writelog() functions is called if a login-attempt has be tried without success
     *
     * @var bool
     */
    public $writeAttemptLog = false;

    /**
     * If the \TYPO3\CMS\Core\Utility\GeneralUtility::devLog() function should be used
     *
     * @var bool
     */
    public $writeDevLog = false;

    /**
     * Initialize authentication service
     *
     * @param string $mode Subtype of the service which is used to call the service.
     * @param array $loginData Submitted login form data
     * @param array $authInfo Information array. Holds submitted form data etc.
     * @param AbstractUserAuthentication $pObj Parent object
     * @return void
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        $this->pObj = $pObj;
        // Sub type
        $this->mode = $mode;
        $this->login = $loginData;
        $this->authInfo = $authInfo;
        $this->db_user = $this->getServiceOption('db_user', $authInfo['db_user'], false);
        $this->db_groups = $this->getServiceOption('db_groups', $authInfo['db_groups'], false);
        $this->writeAttemptLog = $this->pObj->writeAttemptLog;
        $this->writeDevLog = $this->pObj->writeDevLog;
    }

    /**
     * Check the login data with the user record data for builtin login methods
     *
     * @param array $user User data array
     * @param array $loginData Login data array
     * @param string $passwordCompareStrategy Password compare strategy
     * @return bool TRUE if login data matched
     */
    public function compareUident(array $user, array $loginData, $passwordCompareStrategy = '')
    {
        return $this->pObj->compareUident($user, $loginData, $passwordCompareStrategy);
    }

    /**
     * Writes to log database table in pObj
     *
     * @param int $type denotes which module that has submitted the entry. This is the current list:  1=tce_db; 2=tce_file; 3=system (eg. sys_history save); 4=modules; 254=Personal settings changed; 255=login / out action: 1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
     * @param int $action denotes which specific operation that wrote the entry (eg. 'delete', 'upload', 'update' and so on...). Specific for each $type. Also used to trigger update of the interface. (see the log-module for the meaning of each number !!)
     * @param int $error flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
     * @param int $details_nr The message number. Specific for each $type and $action. in the future this will make it possible to translate error messages to other languages
     * @param string $details Default text that follows the message
     * @param array $data Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed the details-text...
     * @param string $tablename Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @param int|string $recuid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @param int|string $recpid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @return void
     */
    public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename = '', $recuid = '', $recpid = '')
    {
        if ($this->writeAttemptLog) {
            $this->pObj->writelog($type, $action, $error, $details_nr, $details, $data, $tablename, $recuid, $recpid);
        }
    }

    /**
     * Get a user from DB by username
     *
     * @param string $username User name
     * @param string $extraWhere Additional WHERE clause: " AND ...
     * @param array|string $dbUserSetup User db table definition, or empty string for $this->db_user
     * @return mixed User array or FALSE
     */
    public function fetchUserRecord($username, $extraWhere = '', $dbUserSetup = '')
    {
        $dbUser = is_array($dbUserSetup) ? $dbUserSetup : $this->db_user;
        $user = $this->pObj->fetchUserRecord($dbUser, $username, $extraWhere);
        return $user;
    }
}
