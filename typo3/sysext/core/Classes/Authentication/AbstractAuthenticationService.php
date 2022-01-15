<?php

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

namespace TYPO3\CMS\Core\Authentication;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Authentication services class
 */
class AbstractAuthenticationService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * If the writelog() functions is called if a login-attempt has be tried without success
     *
     * @var bool
     */
    public $writeAttemptLog = false;

    /**
     * @var array service description array
     */
    public $info = [];

    /**
     * Initialize authentication service
     *
     * @param string $mode Subtype of the service which is used to call the service.
     * @param array $loginData Submitted login form data
     * @param array $authInfo Information array. Holds submitted form data etc.
     * @param AbstractUserAuthentication $pObj Parent object
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        $this->pObj = $pObj;
        // Sub type
        $this->mode = $mode;
        $this->login = $loginData;
        $this->authInfo = $authInfo;
        $this->db_user = $this->getServiceOption('db_user', $authInfo['db_user'] ?? [], false);
        $this->writeAttemptLog = $this->pObj->writeAttemptLog ?? true;
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
        $user = false;
        if ($username || $extraWhere) {
            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($dbUser['table']);
            $query->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $constraints = array_filter([
                QueryHelper::stripLogicalOperatorPrefix($dbUser['check_pid_clause']),
                QueryHelper::stripLogicalOperatorPrefix($dbUser['enable_clause']),
                QueryHelper::stripLogicalOperatorPrefix($extraWhere),
            ]);
            if (!empty($username)) {
                array_unshift(
                    $constraints,
                    $query->expr()->eq(
                        $dbUser['username_column'],
                        $query->createNamedParameter($username, \PDO::PARAM_STR)
                    )
                );
            }
            $user = $query->select('*')
                ->from($dbUser['table'])
                ->where(...$constraints)
                ->executeQuery()
                ->fetchAssociative();
        }
        return $user;
    }

    /**
     * Initialization of the service.
     * This is a stub as needed by GeneralUtility::makeInstanceService()
     * @internal this is part of the Service API which should be avoided to be used and only used within TYPO3 internally
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * Resets the service.
     * This is a stub as needed by GeneralUtility::makeInstanceService()
     * @internal this is part of the Service API which should be avoided to be used and only used within TYPO3 internally
     */
    public function reset()
    {
        // nothing to do
    }

    /**
     * Returns the service key of the service
     *
     * @return string Service key
     * @internal this is part of the Service API which should be avoided to be used and only used within TYPO3 internally
     */
    public function getServiceKey()
    {
        return $this->info['serviceKey'];
    }

    /**
     * Returns the title of the service
     *
     * @return string Service title
     * @internal this is part of the Service API which should be avoided to be used and only used within TYPO3 internally
     */
    public function getServiceTitle()
    {
        return $this->info['title'];
    }

    /**
     * Returns service configuration values from the $TYPO3_CONF_VARS['SVCONF'] array
     *
     * @param string $optionName Name of the config option
     * @param mixed $defaultValue Default configuration if no special config is available
     * @param bool $includeDefaultConfig If set the 'default' config will be returned if no special config for this service is available (default: TRUE)
     * @return mixed Configuration value for the service
     * @internal this is part of the Service API which should be avoided to be used and only used within TYPO3 internally
     */
    public function getServiceOption($optionName, $defaultValue = '', $includeDefaultConfig = true)
    {
        $config = null;
        $serviceType = $this->info['serviceType'] ?? '';
        $serviceKey = $this->info['serviceKey'] ?? '';
        $svOptions = $GLOBALS['TYPO3_CONF_VARS']['SVCONF'][$serviceType] ?? [];
        if (isset($svOptions[$serviceKey][$optionName])) {
            $config = $svOptions[$serviceKey][$optionName];
        } elseif ($includeDefaultConfig && isset($svOptions['default'][$optionName])) {
            $config = $svOptions['default'][$optionName];
        }
        if (!isset($config)) {
            $config = $defaultValue;
        }
        return $config;
    }

    /**
     * @return array
     * @internal this is part of the Service API which should be avoided to be used and only used within TYPO3 internally
     */
    public function getLastErrorArray(): array
    {
        return [];
    }
}
