<?php
declare(strict_types = 1);

namespace TYPO3\CMS\FrontendLogin\Domain\Repository;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\FrontendLogin\Service\UserService;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class FrontendUserGroupRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $table;

    /**
     * @param UserService $userService
     * @param ConnectionPool $connectionPool
     */
    public function __construct(UserService $userService, ConnectionPool $connectionPool)
    {
        $this->table = $userService->getFeUserGroupTable();
        $this->connection = $connectionPool->getConnectionForTable($this->getTable());
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param int $groupId
     * @return int|null
     */
    public function findRedirectPageIdByGroupId(int $groupId): ?int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $query = $queryBuilder
            ->select('felogin_redirectPid')
            ->from($this->getTable())
            ->where(
                $queryBuilder->expr()->neq(
                    'felogin_redirectPid',
                    $this->connection->quote('')
                ),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($groupId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
        ;

        $column = $query->execute()->fetchColumn();
        return $column === false ? null : (int)$column;
    }
}
