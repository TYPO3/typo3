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

namespace TYPO3\CMS\FrontendLogin\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class FrontendUserRepository
{
    protected readonly Connection $connection;

    public function __construct(
        protected readonly Context $context,
        ConnectionPool $connectionPool,
    ) {
        $this->connection = $connectionPool->getConnectionForTable('fe_users');
    }

    /**
     * Change the password for a user based on forgot password hash.
     */
    public function updatePasswordAndInvalidateHash(string $forgotPasswordHash, string $hashedPassword): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $currentTimestamp = $this->context->getPropertyFromAspect('date', 'timestamp');
        $query = $queryBuilder
            ->update('fe_users')
            ->set('password', $hashedPassword)
            ->set('felogin_forgotHash', $this->connection->quote(''), false)
            ->set('tstamp', $currentTimestamp)
            ->where($queryBuilder->expr()->eq('felogin_forgotHash', $queryBuilder->createNamedParameter($forgotPasswordHash)));
        $query->executeStatement();
    }

    /**
     * Returns true if a user exists with hash as `felogin_forgothash`, otherwise false.
     */
    public function existsUserWithHash(string $hash): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->count('uid')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('felogin_forgotHash', $queryBuilder->createNamedParameter($hash)));
        return (bool)$query->executeQuery()->fetchOne();
    }

    /**
     * Sets forgot hash for passed user uid.
     */
    public function updateForgotHashForUserByUid(int $uid, string $hash): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->update('fe_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, $this->connection::PARAM_INT)))
            ->set('felogin_forgotHash', $hash);
        $query->executeStatement();
    }

    /**
     * Fetches an array with all columns (except the password) from the fe_users table for the given username or
     * email on the given pages. Returns null, if user was not found or if user has no email address set.
     */
    public function findUserByUsernameOrEmailOnPages(string $usernameOrEmail, array $pages = []): ?array
    {
        if ($usernameOrEmail === '') {
            return null;
        }
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($usernameOrEmail)),
                    $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($usernameOrEmail)),
                ),
                $queryBuilder->expr()->neq('email', $this->connection->quote('')),
            );
        if (!empty($pages)) {
            // respect storage pid
            $query->andWhere($queryBuilder->expr()->in('pid', $pages));
        }
        $result = $query->executeQuery()->fetchAssociative() ?: null;
        if ($result) {
            unset($result['password']);
        }
        return $result;
    }

    public function findOneByForgotPasswordHash(string $hash): ?array
    {
        if ($hash === '') {
            return null;
        }
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('felogin_forgotHash', $queryBuilder->createNamedParameter($hash)))
            ->setMaxResults(1);
        $row = $query->executeQuery()->fetchAssociative();
        return is_array($row) ? $row : null;
    }

    public function findRedirectIdPageByUserId(int $uid): ?int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $query = $queryBuilder
            ->select('felogin_redirectPid')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->neq('felogin_redirectPid', $this->connection->quote('')),
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->setMaxResults(1);
        $column = $query->executeQuery()->fetchOne();
        return $column === false ? null : (int)$column;
    }
}
