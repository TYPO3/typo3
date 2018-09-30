<?php
namespace TYPO3\CMS\Core\Authentication;

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

use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Authentication services class
 */
class AuthenticationService extends AbstractAuthenticationService
{
    /**
     * Process the submitted credentials.
     * In this case hash the clear text password if it has been submitted.
     *
     * @param array $loginData Credentials that are submitted and potentially modified by other services
     * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
     * @return bool
     */
    public function processLoginData(array &$loginData, $passwordTransmissionStrategy)
    {
        $isProcessed = false;
        if ($passwordTransmissionStrategy === 'normal') {
            $loginData['uident_text'] = $loginData['uident'];
            $isProcessed = true;
        }
        return $isProcessed;
    }

    /**
     * Find a user (eg. look up the user record in database when a login is sent)
     *
     * @return mixed User array or FALSE
     */
    public function getUser()
    {
        if ($this->login['status'] !== LoginType::LOGIN) {
            return false;
        }
        if ((string)$this->login['uident_text'] === '') {
            // Failed Login attempt (no password given)
            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP### for username \'%s\' with an empty password!', [
                $this->login['uname']
            ]);
            $this->logger->warning(sprintf('Login-attempt from %s, for username \'%s\' with an empty password!', $this->authInfo['REMOTE_ADDR'], $this->login['uname']));
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname']);
        if (!is_array($user)) {
            // Failed login attempt (no username found)
            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP###, username \'%s\' not found!!', [$this->login['uname']]);
            $this->logger->info('Login-attempt from username \'' . $this->login['uname'] . '\' not found!', [
                'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR']
            ]);
        } else {
            $this->logger->debug('User found', [
                $this->db_user['userid_column'] => $user[$this->db_user['userid_column']],
                $this->db_user['username_column'] => $user[$this->db_user['username_column']]
            ]);
        }
        return $user;
    }

    /**
     * Authenticate a user: Check submitted user credentials against stored hashed password,
     * check domain lock if configured.
     *
     * Returns one of the following status codes:
     *  >= 200: User authenticated successfully. No more checking is needed by other auth services.
     *  >= 100: User not authenticated; this service is not responsible. Other auth services will be asked.
     *  > 0:    User authenticated successfully. Other auth services will still be asked.
     *  <= 0:   Authentication failed, no more checking needed by other auth services.
     *
     * @param array $user User data
     * @return int Authentication status code, one of 0, 100, 200
     */
    public function authUser(array $user): int
    {
        // Early 100 "not responsible, check other services" if username or password is empty
        if (!isset($this->login['uident_text']) || (string)$this->login['uident_text'] === ''
            || !isset($this->login['uname']) || (string)$this->login['uname'] === '') {
            return 100;
        }

        if (empty($this->db_user['table'])) {
            throw new \RuntimeException('User database table not set', 1533159150);
        }

        $submittedUsername = (string)$this->login['uname'];
        $submittedPassword = (string)$this->login['uident_text'];
        $passwordHashInDatabase = $user['password'];
        $queriedDomain = $this->authInfo['HTTP_HOST'];
        $configuredDomainLock = $user['lockToDomain'];
        $userDatabaseTable = $this->db_user['table'];

        $isSaltedPassword = false;
        $isValidPassword = false;
        $isReHashNeeded = false;
        $isDomainLockMet = false;

        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);

        // Get a hashed password instance for the hash stored in db of this user
        $invalidPasswordHashException = null;
        try {
            $hashInstance = $saltFactory->get($passwordHashInDatabase, TYPO3_MODE);
        } catch (InvalidPasswordHashException $invalidPasswordHashException) {
            // This can be refactored if the 'else' part below is gone in TYPO3 v10.0: Log and return 100 here
            $hashInstance = null;
        }
        // An instance of the currently configured salted password mechanism
        // Don't catch InvalidPasswordHashException here: Only install tool should handle those configuration failures
        $defaultHashInstance = $saltFactory->getDefaultHashInstance(TYPO3_MODE);

        if ($hashInstance instanceof PasswordHashInterface) {
            // We found a hash class that can handle this type of hash
            $isSaltedPassword = true;
            $isValidPassword = $hashInstance->checkPassword($submittedPassword, $passwordHashInDatabase);
            if ($isValidPassword) {
                if ($hashInstance->isHashUpdateNeeded($passwordHashInDatabase)
                    || $defaultHashInstance != $hashInstance
                ) {
                    // Lax object comparison intended: Rehash if old and new salt objects are not
                    // instances of the same class.
                    $isReHashNeeded = true;
                }
                if (empty($configuredDomainLock)) {
                    // No domain restriction set for user in db. This is ok.
                    $isDomainLockMet = true;
                } elseif (!strcasecmp($configuredDomainLock, $queriedDomain)) {
                    // Domain restriction set and it matches given host. Ok.
                    $isDomainLockMet = true;
                }
            }
        } elseif (substr($user['password'], 0, 2) === 'M$') {
            // @todo @deprecated: The entire else should be removed in TYPO3 v10.0 as dedicated breaking patch
            // If the stored db password starts with M$, it may be a md5 password that has been
            // upgraded to a salted md5 using the old salted passwords scheduler task.
            // See if a salt instance is returned if we cut off the M, so Md5PasswordHash kicks in
            try {
                $hashInstance = $saltFactory->get(substr($passwordHashInDatabase, 1), TYPO3_MODE);
                $isSaltedPassword = true;
                $isValidPassword = $hashInstance->checkPassword(md5($submittedPassword), substr($passwordHashInDatabase, 1));
                if ($isValidPassword) {
                    // Upgrade this password to a sane mechanism now
                    $isReHashNeeded = true;
                    if (empty($configuredDomainLock)) {
                        // No domain restriction set for user in db. This is ok.
                        $isDomainLockMet = true;
                    } elseif (!strcasecmp($configuredDomainLock, $queriedDomain)) {
                        // Domain restriction set and it matches given host. Ok.
                        $isDomainLockMet = true;
                    }
                }
            } catch (InvalidPasswordHashException $e) {
                // Still no instance found: $isSaltedPasswords is NOT set to true, logging and return done below
            }
        } else {
            // @todo: Simplify if elseif part is gone
            // Still no valid hash instance could be found. Probably the stored hash used a mechanism
            // that is not available on current system. We throw the previous exception again to be
            // handled on a higher level.
            if ($invalidPasswordHashException !== null) {
                throw $invalidPasswordHashException;
            }
        }

        if (!$isSaltedPassword) {
            // Could not find a responsible hash algorithm for given password. This is unusual since other
            // authentication services would usually be called before this one with higher priority. We thus log
            // the failed login but still return '100' to proceed with other services that may follow.
            $message = 'Login-attempt from ###IP###, username \'%s\', no suitable hash method found!';
            $this->writeLogMessage($message, $submittedUsername);
            $this->writelog(255, 3, 3, 1, $message, [$submittedUsername]);
            $this->logger->info(sprintf($message, $submittedUsername));
            // Not responsible, check other services
            return 100;
        }

        if (!$isValidPassword) {
            // Failed login attempt - wrong password
            $this->writeLogMessage(TYPO3_MODE . ' Authentication failed - wrong password for username \'%s\'', $submittedUsername);
            $message = 'Login-attempt from ###IP###, username \'%s\', password not accepted!';
            $this->writelog(255, 3, 3, 1, $message, [$submittedUsername]);
            $this->logger->info(sprintf($message, $submittedUsername));
            // Responsible, authentication failed, do NOT check other services
            return 0;
        }

        if (!$isDomainLockMet) {
            // Password ok, but configured domain lock not met
            $errorMessage = 'Login-attempt from ###IP###, username \'%s\', locked domain \'%s\' did not match \'%s\'!';
            $this->writeLogMessage($errorMessage, $user[$this->db_user['username_column']], $configuredDomainLock, $queriedDomain);
            $this->writelog(255, 3, 3, 1, $errorMessage, [$user[$this->db_user['username_column']], $configuredDomainLock, $queriedDomain]);
            $this->logger->info(sprintf($errorMessage, $user[$this->db_user['username_column']], $configuredDomainLock, $queriedDomain));
            // Responsible, authentication ok, but domain lock not ok, do NOT check other services
            return 0;
        }

        if ($isReHashNeeded) {
            // Given password validated but a re-hash is needed. Do so.
            $this->updatePasswordHashInDatabase(
                $userDatabaseTable,
                (int)$user['uid'],
                $defaultHashInstance->getHashedPassword($submittedPassword)
            );
        }

        // Responsible, authentication ok, domain lock ok. Log successful login and return 'auth ok, do NOT check other services'
        $this->writeLogMessage(TYPO3_MODE . ' Authentication successful for username \'%s\'', $submittedUsername);
        return 200;
    }

    /**
     * Find usergroup records, currently only for frontend
     *
     * @param array $user Data of user.
     * @param array $knownGroups Group data array of already known groups. This is handy if you want select other related groups. Keys in this array are unique IDs of those groups.
     * @return mixed Groups array, keys = uid which must be unique
     */
    public function getGroups($user, $knownGroups)
    {
        // Attention: $knownGroups is not used within this method, but other services can use it.
        // This parameter should not be removed!
        // The FrontendUserAuthentication call getGroups and handover the previous detected groups.
        $groupDataArr = [];
        if ($this->mode === 'getGroupsFE') {
            $groups = [];
            if ($user[$this->db_user['usergroup_column']] ?? false) {
                $groupList = $user[$this->db_user['usergroup_column']];
                $groups = [];
                $this->getSubGroups($groupList, '', $groups);
            }
            // ADD group-numbers if the IPmask matches.
            foreach ($GLOBALS['TYPO3_CONF_VARS']['FE']['IPmaskMountGroups'] ?? [] as $IPel) {
                if ($this->authInfo['REMOTE_ADDR'] && $IPel[0] && GeneralUtility::cmpIP($this->authInfo['REMOTE_ADDR'], $IPel[0])) {
                    $groups[] = (int)$IPel[1];
                }
            }
            $groups = array_unique($groups);
            if (!empty($groups)) {
                $this->logger->debug('Get usergroups with id: ' . implode(',', $groups));
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($this->db_groups['table']);
                if (!empty($this->authInfo['showHiddenRecords'])) {
                    $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
                }

                $res = $queryBuilder->select('*')
                    ->from($this->db_groups['table'])
                    ->where(
                        $queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($groups, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->eq(
                                'lockToDomain',
                                $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                            ),
                            $queryBuilder->expr()->isNull('lockToDomain'),
                            $queryBuilder->expr()->eq(
                                'lockToDomain',
                                $queryBuilder->createNamedParameter($this->authInfo['HTTP_HOST'], \PDO::PARAM_STR)
                            )
                        )
                    )
                    ->execute();

                while ($row = $res->fetch()) {
                    $groupDataArr[$row['uid']] = $row;
                }
            } else {
                $this->logger->debug('No usergroups found.');
            }
        }
        return $groupDataArr;
    }

    /**
     * Fetches subgroups of groups. Function is called recursively for each subgroup.
     * Function was previously copied from
     * \TYPO3\CMS\Core\Authentication\BackendUserAuthentication->fetchGroups and has been slightly modified.
     *
     * @param string $grList Commalist of fe_groups uid numbers
     * @param string $idList List of already processed fe_groups-uids so the function will not fall into an eternal recursion.
     * @param array $groups
     * @internal
     */
    public function getSubGroups($grList, $idList, &$groups)
    {
        // Fetching records of the groups in $grList (which are not blocked by lockedToDomain either):
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
        if (!empty($this->authInfo['showHiddenRecords'])) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $res = $queryBuilder
            ->select('uid', 'subgroup')
            ->from($this->db_groups['table'])
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $grList, true),
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'lockToDomain',
                        $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->isNull('lockToDomain'),
                    $queryBuilder->expr()->eq(
                        'lockToDomain',
                        $queryBuilder->createNamedParameter($this->authInfo['HTTP_HOST'], \PDO::PARAM_STR)
                    )
                )
            )
            ->execute();

        // Internal group record storage
        $groupRows = [];
        // The groups array is filled
        while ($row = $res->fetch()) {
            if (!in_array($row['uid'], $groups)) {
                $groups[] = $row['uid'];
            }
            $groupRows[$row['uid']] = $row;
        }
        // Traversing records in the correct order
        $include_staticArr = GeneralUtility::intExplode(',', $grList);
        // traversing list
        foreach ($include_staticArr as $uid) {
            // Get row:
            $row = $groupRows[$uid];
            // Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist
            if (is_array($row) && !GeneralUtility::inList($idList, $uid) && trim($row['subgroup'])) {
                // Make integer list
                $theList = implode(',', GeneralUtility::intExplode(',', $row['subgroup']));
                // Call recursively, pass along list of already processed groups so they are not processed again.
                $this->getSubGroups($theList, $idList . ',' . $uid, $groups);
            }
        }
    }

    /**
     * Method updates a FE/BE user record - in this case a new password string will be set.
     *
     * @param string $table Database table of this user, usually 'be_users' or 'fe_users'
     * @param int $uid uid of user record that will be updated
     * @param string $newPassword Field values as key=>value pairs to be updated in database
     */
    protected function updatePasswordHashInDatabase(string $table, int $uid, string $newPassword): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection->update(
            $table,
            ['password' => $newPassword],
            ['uid' => $uid]
        );
        $this->logger->notice('Automatic password update for user record in ' . $table . ' with uid ' . $uid);
    }

    /**
     * Writes log message. Destination log depends on the current system mode.
     *
     * This function accepts variable number of arguments and can format
     * parameters. The syntax is the same as for sprintf()
     *
     * @param string $message Message to output
     * @param array<int, mixed> $params
     */
    protected function writeLogMessage(string $message, ...$params): void
    {
        if (!empty($params)) {
            $message = vsprintf($message, $params);
        }
        if (TYPO3_MODE === 'FE') {
            $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
            $timeTracker->setTSlogMessage($message);
        }
        $this->logger->notice($message);
    }
}
