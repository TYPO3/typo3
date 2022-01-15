<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Authentication;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is responsible for
 * - find the right user, sending out a reset email.
 * - create a token for creating the link (not exposed outside of this class)
 * - validate a hashed token
 * - send out an email to initiate the password reset
 * - update a password for a backend user if all parameters match
 *
 * @internal this is a concrete implementation for User/Password login and not part of public TYPO3 Core API.
 */
class PasswordReset implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TOKEN_VALID_UNTIL = '+2 hours';
    protected const MAXIMUM_RESET_ATTEMPTS = 3;
    protected const MAXIMUM_RESET_ATTEMPTS_SINCE = '-30 minutes';

    /**
     * Check if there are at least one in the system that contains a non-empty password AND an email address set.
     */
    public function isEnabled(): bool
    {
        // Option not explicitly enabled
        if (!($GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] ?? false)) {
            return false;
        }
        $queryBuilder = $this->getPreparedQueryBuilder();
        $statement = $queryBuilder
            ->select('uid')
            ->from('be_users')
            ->setMaxResults(1)
            ->executeQuery();
        return (int)$statement->fetchOne() > 0;
    }

    /**
     * Check if a specific backend user can be used to trigger an email reset for (email + password set)
     *
     * @param int $userId
     * @return bool
     */
    public function isEnabledForUser(int $userId): bool
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        $statement = $queryBuilder
            ->select('uid')
            ->from('be_users')
            ->andWhere(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userId, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery();
        return $statement->fetchOne() > 0;
    }

    /**
     * Determine the right user and send out an email. If multiple users are found with the same email address
     * an alternative email is sent.
     *
     * If no user is found, this is logged to the system (but not to sys_log).
     *
     * The method intentionally does not return anything to avoid any information disclosure or exposure.
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @param string $emailAddress
     */
    public function initiateReset(ServerRequestInterface $request, Context $context, string $emailAddress): void
    {
        if (!GeneralUtility::validEmail($emailAddress)) {
            return;
        }
        if ($this->hasExceededMaximumAttemptsForReset($context, $emailAddress)) {
            $this->logger->alert('Password reset requested for email {email} but was requested too many times.', ['email' => $emailAddress]);
            return;
        }
        $queryBuilder = $this->getPreparedQueryBuilder();
        $users = $queryBuilder
            ->select('uid', 'email', 'username', 'realName', 'lang')
            ->from('be_users')
            ->andWhere(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($emailAddress))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        if (!is_array($users) || count($users) === 0) {
            // No user found, do nothing, also no log to sys_log in order avoid log flooding
            $this->logger->warning('Password reset requested for email but no valid users');
        } elseif (count($users) > 1) {
            // More than one user with the same email address found, send out the email that one cannot send out a reset link
            $this->sendAmbiguousEmail($request, $context, $emailAddress);
        } else {
            $user = reset($users);
            $this->sendResetEmail($request, $context, (array)$user, $emailAddress);
        }
    }

    /**
     * Send out an email to a given email address and note that a reset was triggered but email was used multiple times.
     * Used when the database returned multiple users.
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @param string $emailAddress
     */
    protected function sendAmbiguousEmail(ServerRequestInterface $request, Context $context, string $emailAddress): void
    {
        $emailObject = GeneralUtility::makeInstance(FluidEmail::class);
        $emailObject
            ->to(new Address($emailAddress))
            ->setRequest($request)
            ->assign('email', $emailAddress)
            ->setTemplate('PasswordReset/AmbiguousResetRequested');

        GeneralUtility::makeInstance(Mailer::class)->send($emailObject);
        $this->logger->warning('Password reset sent to email address {email} but multiple accounts found', ['email' => $emailAddress]);
        $this->log(
            'Sent password reset email to email address %s but with multiple accounts attached.',
            SystemLogLoginAction::PASSWORD_RESET_REQUEST,
            SystemLogErrorClassification::WARNING,
            0,
            [
                'email' => $emailAddress,
            ],
            NormalizedParams::createFromRequest($request)->getRemoteAddress(),
            $context
        );
    }

    /**
     * Send out an email to a user that does have an email address added to his account, containing a reset link.
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @param array $user
     * @param string $emailAddress
     */
    protected function sendResetEmail(ServerRequestInterface $request, Context $context, array $user, string $emailAddress): void
    {
        $resetLink = $this->generateResetLinkForUser($context, (int)$user['uid'], (string)$user['email']);
        $emailObject = GeneralUtility::makeInstance(FluidEmail::class);
        $emailObject
            ->to(new Address((string)$user['email'], $user['realName']))
            ->setRequest($request)
            ->assign('name', $user['realName'])
            ->assign('email', $user['email'])
            ->assign('language', $user['lang'] ?: 'default')
            ->assign('resetLink', $resetLink)
            ->setTemplate('PasswordReset/ResetRequested');

        GeneralUtility::makeInstance(Mailer::class)->send($emailObject);
        $this->logger->info('Sent password reset email to email address {email} for user {username}', [
            'email' => $emailAddress,
            'username' => $user['username'],
        ]);
        $this->log(
            'Sent password reset email to email address %s',
            SystemLogLoginAction::PASSWORD_RESET_REQUEST,
            SystemLogErrorClassification::SECURITY_NOTICE,
            (int)$user['uid'],
            [
                'email' => $user['email'],
            ],
            NormalizedParams::createFromRequest($request)->getRemoteAddress(),
            $context
        );
    }

    /**
     * Creates a token, stores it in the database, and then creates an absolute URL for resetting the password.
     * This is all in one method so it is not exposed from the outside.
     *
     * This function requires:
     * a) the user is allowed to do a password reset (no check is done anymore)
     * b) a valid email address.
     *
     * @param Context $context
     * @param int $userId the backend user uid
     * @param string $emailAddress is part of the hash to ensure that the email address does not get reset.
     * @return UriInterface
     */
    protected function generateResetLinkForUser(Context $context, int $userId, string $emailAddress): UriInterface
    {
        $token = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(96);
        $currentTime = $context->getAspect('date')->getDateTime();
        $expiresOn = $currentTime->modify(self::TOKEN_VALID_UNTIL);
        // Create a hash ("one time password") out of the token including the timestamp of the expiration date
        $hash = GeneralUtility::hmac($token . '|' . (string)$expiresOn->getTimestamp() . '|' . $emailAddress . '|' . (string)$userId, 'password-reset');

        // Set the token in the database, which is hashed
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->update('be_users', ['password_reset_token' => $this->getHasher()->getHashedPassword($hash)], ['uid' => $userId]);

        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'password_reset_validate',
            [
                // "token"
                't' => $token,
                // "expiration date"
                'e' => $expiresOn->getTimestamp(),
                // "identity"
                'i' => hash('sha1', $emailAddress . (string)$userId),
            ],
            UriBuilder::ABSOLUTE_URL
        );
    }

    /**
     * Validates all query parameters / GET parameters of the given request against the token.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function isValidResetTokenFromRequest(ServerRequestInterface $request): bool
    {
        $user = $this->findValidUserForToken(
            (string)($request->getQueryParams()['t'] ?? ''),
            (string)($request->getQueryParams()['i'] ?? ''),
            (int)($request->getQueryParams()['e'] ?? 0)
        );
        return $user !== null;
    }

    /**
     * Fetch the user record from the database if the token is valid, and has matched all criteria
     *
     * @param string $token
     * @param string $identity
     * @param int $expirationTimestamp
     * @return array|null the BE User database record
     */
    protected function findValidUserForToken(string $token, string $identity, int $expirationTimestamp): ?array
    {
        $user = null;
        // Find the token in the database
        $queryBuilder = $this->getPreparedQueryBuilder();

        $queryBuilder
            ->select('uid', 'email', 'password_reset_token')
            ->from('be_users');
        if ($queryBuilder->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->comparison('SHA1(CONCAT(' . $queryBuilder->quoteIdentifier('email') . ', ' . $queryBuilder->quoteIdentifier('uid') . '))', $queryBuilder->expr()::EQ, $queryBuilder->createNamedParameter($identity))
            );
            $user = $queryBuilder->executeQuery()->fetchAssociative();
        } else {
            // no native SHA1/ CONCAT functionality, has to be done in PHP
            $stmt = $queryBuilder->executeQuery();
            while ($row = $stmt->fetchAssociative()) {
                if (hash_equals(hash('sha1', $row['email'] . (string)$row['uid']), $identity)) {
                    $user = $row;
                    break;
                }
            }
        }

        if (!is_array($user) || empty($user)) {
            return null;
        }

        // Validate hash by rebuilding the hash from the parameters and the URL and see if this matches against the stored password_reset_token
        $hash = GeneralUtility::hmac($token . '|' . (string)$expirationTimestamp . '|' . $user['email'] . '|' . (string)$user['uid'], 'password-reset');
        if (!$this->getHasher()->checkPassword($hash, $user['password_reset_token'] ?? '')) {
            return null;
        }
        return $user;
    }

    /**
     * Update the password in the database if the password matches and the token is valid.
     *
     * @param ServerRequestInterface $request
     * @param Context $context current context
     * @return bool whether the password was reset or not
     */
    public function resetPassword(ServerRequestInterface $request, Context $context): bool
    {
        $expirationTimestamp = (int)($request->getQueryParams()['e'] ?? '');
        $identityHash = (string)($request->getQueryParams()['i'] ?? '');
        $token = (string)($request->getQueryParams()['t'] ?? '');
        $newPassword = (string)($request->getParsedBody()['password'] ?? '');
        $newPasswordRepeat = (string)($request->getParsedBody()['passwordrepeat'] ?? '');
        if (strlen($newPassword) < 8 || $newPassword !== $newPasswordRepeat) {
            $this->logger->debug('Password reset not possible due to weak password');
            return false;
        }
        $user = $this->findValidUserForToken($token, $identityHash, $expirationTimestamp);
        if ($user === null) {
            $this->logger->warning('Password reset not possible. Valid user for token not found.');
            return false;
        }
        $userId = (int)$user['uid'];

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->update('be_users', ['password_reset_token' => '', 'password' => $this->getHasher()->getHashedPassword($newPassword)], ['uid' => $userId]);

        $this->logger->info('Password reset successful for user {user_id)', ['user_id' => $userId]);
        $this->log(
            'Password reset successful for user %s',
            SystemLogLoginAction::PASSWORD_RESET_ACCOMPLISHED,
            SystemLogErrorClassification::SECURITY_NOTICE,
            $userId,
            [
                'email' => $user['email'],
                'user' => $userId,
            ],
            NormalizedParams::createFromRequest($request)->getRemoteAddress(),
            $context
        );
        return true;
    }

    /**
     * The querybuilder for finding the right user - and adds some restrictions:
     * - No CLI users
     * - No Admin users (with option)
     * - No hidden/deleted users
     * - Password must be set
     * - Username must be set
     * - Email address must be set
     *
     * @return QueryBuilder
     */
    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(RootLevelRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(StartTimeRestriction::class))
            ->add(GeneralUtility::makeInstance(EndTimeRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $queryBuilder->where(
            $queryBuilder->expr()->neq('username', $queryBuilder->createNamedParameter('')),
            $queryBuilder->expr()->neq('username', $queryBuilder->createNamedParameter('_cli_')),
            $queryBuilder->expr()->neq('password', $queryBuilder->createNamedParameter('')),
            $queryBuilder->expr()->neq('email', $queryBuilder->createNamedParameter(''))
        );
        if (!($GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] ?? false)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
        }
        return $queryBuilder;
    }

    protected function getHasher(): PasswordHashInterface
    {
        return GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
    }

    /**
     * Adds an entry to "sys_log", also used to track the maximum allowed attempts.
     *
     * @param string $message the information / message in english
     * @param int $action see SystemLogLoginAction
     * @param int $error see SystemLogErrorClassification
     * @param int $userId
     * @param array $data additional information, used for the message
     * @param string $ipAddress
     * @param Context $context
     */
    protected function log(string $message, int $action, int $error, int $userId, array $data, $ipAddress, Context $context): void
    {
        $fields = [
            'userid' => $userId,
            'type' => SystemLogType::LOGIN,
            'channel' => SystemLogType::toChannel(SystemLogType::LOGIN),
            'level' => SystemLogType::toLevel(SystemLogType::LOGIN),
            'action' => $action,
            'error' => $error,
            'details_nr' => 1,
            'details' => $message,
            'log_data' => serialize($data),
            'tablename' => 'be_users',
            'recuid' => $userId,
            'IP' => (string)$ipAddress,
            'tstamp' => $context->getAspect('date')->get('timestamp'),
            'event_pid' => 0,
            'NEWid' => '',
            'workspace' => 0,
        ];

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_log');
        $connection->insert(
            'sys_log',
            $fields,
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
            ]
        );
    }

    /**
     * Checks if an email reset link has been requested more than 3 times in the last 30mins.
     * If a password was successfully reset more than three times in 30 minutes, it would still fail.
     *
     * @param Context $context
     * @param string $email
     * @return bool
     */
    protected function hasExceededMaximumAttemptsForReset(Context $context, string $email): bool
    {
        $now = $context->getAspect('date')->getDateTime();
        $numberOfAttempts = $this->getNumberOfInitiatedResetsForEmail($now->modify(self::MAXIMUM_RESET_ATTEMPTS_SINCE), $email);
        return $numberOfAttempts > self::MAXIMUM_RESET_ATTEMPTS;
    }

    /**
     * SQL query to find the amount of initiated resets from a given time.
     *
     * @param \DateTimeInterface $since
     * @param string $email
     * @return int
     */
    protected function getNumberOfInitiatedResetsForEmail(\DateTimeInterface $since, string $email): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(SystemLogType::LOGIN)),
                $queryBuilder->expr()->eq('action', $queryBuilder->createNamedParameter(SystemLogLoginAction::PASSWORD_RESET_REQUEST)),
                $queryBuilder->expr()->eq('log_data', $queryBuilder->createNamedParameter(serialize(['email' => $email]))),
                $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($since->getTimestamp(), \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
    }
}
