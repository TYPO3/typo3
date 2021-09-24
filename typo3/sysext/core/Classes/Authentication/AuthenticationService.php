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

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
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
            $loginData = array_map('trim', $loginData);
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
            $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::ATTEMPT, SystemLogErrorClassification::SECURITY_NOTICE, 2, 'Login-attempt from ###IP### for username \'%s\' with an empty password!', [
                $this->login['uname'],
            ]);
            $this->logger->warning('Login-attempt from {ip}, for username "{username}" with an empty password!', [
                'ip' => $this->authInfo['REMOTE_ADDR'],
                'username' => $this->login['uname'],
            ]);
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname']);
        if (!is_array($user)) {
            // Failed login attempt (no username found)
            $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::ATTEMPT, SystemLogErrorClassification::SECURITY_NOTICE, 2, 'Login-attempt from ###IP###, username \'%s\' not found!', [$this->login['uname']]);
            $this->logger->info('Login-attempt from username "{username}" not found!', [
                'username' => $this->login['uname'],
                'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR'],
            ]);
        } else {
            $this->logger->debug('User found', [
                $this->db_user['userid_column'] => $user[$this->db_user['userid_column']],
                $this->db_user['username_column'] => $user[$this->db_user['username_column']],
            ]);
        }
        return $user;
    }

    /**
     * Authenticate a user: Check submitted user credentials against stored hashed password.
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
        $userDatabaseTable = $this->db_user['table'];

        $isReHashNeeded = false;

        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);

        // Get a hashed password instance for the hash stored in db of this user
        try {
            $hashInstance = $saltFactory->get($passwordHashInDatabase, $this->pObj->loginType);
        } catch (InvalidPasswordHashException $exception) {
            // Could not find a responsible hash algorithm for given password. This is unusual since other
            // authentication services would usually be called before this one with higher priority. We thus log
            // the failed login but still return '100' to proceed with other services that may follow.
            $message = 'Login-attempt from ###IP###, username \'%s\', no suitable hash method found!';
            $this->writeLogMessage($message, $submittedUsername);
            $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::ATTEMPT, SystemLogErrorClassification::SECURITY_NOTICE, 1, $message, [$submittedUsername]);
            // Not responsible, check other services
            return 100;
        }

        // An instance of the currently configured salted password mechanism
        // Don't catch InvalidPasswordHashException here: Only install tool should handle those configuration failures
        $defaultHashInstance = $saltFactory->getDefaultHashInstance($this->pObj->loginType);

        // We found a hash class that can handle this type of hash
        $isValidPassword = $hashInstance->checkPassword($submittedPassword, $passwordHashInDatabase);
        if ($isValidPassword) {
            if ($hashInstance->isHashUpdateNeeded($passwordHashInDatabase)
                || $defaultHashInstance != $hashInstance
            ) {
                // Lax object comparison intended: Rehash if old and new salt objects are not
                // instances of the same class.
                $isReHashNeeded = true;
            }
        }

        if (!$isValidPassword) {
            // Failed login attempt - wrong password
            $message = 'Login-attempt from ###IP###, username \'%s\', password not accepted!';
            $this->writeLogMessage($message, $submittedUsername);
            $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::ATTEMPT, SystemLogErrorClassification::SECURITY_NOTICE, 1, $message, [$submittedUsername]);
            // Responsible, authentication failed, do NOT check other services
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

        // Responsible, authentication ok. Log successful login and return 'auth ok, do NOT check other services'
        $this->writeLogMessage($this->pObj->loginType . ' Authentication successful for username \'%s\'', $submittedUsername);
        return 200;
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
        $this->logger->notice('Automatic password update for user record in {table} with uid {uid}', [
            'table' => $table,
            'uid' => $uid,
        ]);
    }

    /**
     * Writes log message. Destination log depends on the current system mode.
     *
     * This function accepts variable number of arguments and can format
     * parameters. The syntax is the same as for sprintf()
     * If a marker ###IP### is present in the message, it is automatically replaced with the REMOTE_ADDR
     *
     * @param string $message Message to output
     * @param array<int,mixed> $params
     */
    protected function writeLogMessage(string $message, ...$params): void
    {
        if (!empty($params)) {
            $message = vsprintf($message, $params);
        }
        $message = str_replace('###IP###', (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'), $message);
        if ($this->pObj->loginType === 'FE') {
            $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
            $timeTracker->setTSlogMessage($message, LogLevel::INFO);
        }
        $this->logger->notice($message);
    }
}
