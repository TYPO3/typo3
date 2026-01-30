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

namespace TYPO3\CMS\Backend\Backend\Bookmark;

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BookmarkRepository
{
    protected const TABLE_NAME = 'sys_be_shortcuts';
    protected const GROUP_TABLE_NAME = 'sys_be_shortcuts_group';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {}

    public function findById(int $id): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? $row : null;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array> Indexed by bookmark ID
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery();

        $bookmarks = [];
        while ($row = $result->fetchAssociative()) {
            $bookmarks[(int)$row['uid']] = $row;
        }

        return $bookmarks;
    }

    public function findByUser(int $userId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $constraints = [];

        // User's own bookmarks with a non-negative sc_group value.
        // Bookmarks in user-created groups also match here, as their sc_group defaults to 0.
        $constraints[] = $queryBuilder->expr()->and(
            $queryBuilder->expr()->eq(
                'userid',
                $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->gte(
                'sc_group',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            )
        );

        // Global bookmarks (negative sc_group) - visible to all users
        $constraints[] = $queryBuilder->expr()->lt(
            'sc_group',
            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
        );

        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->or(...$constraints))
            ->orderBy('sc_group')
            ->addOrderBy('sorting')
            ->executeQuery();

        $bookmarks = [];
        while ($row = $result->fetchAssociative()) {
            $bookmarks[] = $row;
        }

        return $bookmarks;
    }

    public function exists(int $userId, string $routeIdentifier, string $arguments): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder->select('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('route', $queryBuilder->createNamedParameter($routeIdentifier)),
                $queryBuilder->expr()->eq('arguments', $queryBuilder->createNamedParameter($arguments))
            )
            ->executeQuery()
            ->fetchOne();

        return (bool)$uid;
    }

    /**
     * @return int|false The new bookmark ID or false on failure
     */
    public function insert(int $userId, string $routeIdentifier, string $arguments, string $title): int|false
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $affectedRows = $connection->insert(
            self::TABLE_NAME,
            [
                'userid' => $userId,
                'route' => $routeIdentifier,
                'arguments' => $arguments,
                'description' => $title ?: 'Bookmark',
                'sorting' => $GLOBALS['EXEC_TIME'],
            ]
        );

        if ($affectedRows === 1) {
            return (int)$connection->lastInsertId();
        }

        return false;
    }

    public function update(
        int $id,
        ?int $userId,
        string $title,
        int|string $groupId,
        bool $allowGlobalGroups = true
    ): int {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->set('description', $title);

        if (is_string($groupId)) {
            $queryBuilder->set('group_uuid', $groupId);
            $queryBuilder->set('sc_group', BookmarkService::GROUP_DEFAULT);
        } else {
            $effectiveGroupId = $allowGlobalGroups ? $groupId : max(BookmarkService::GROUP_DEFAULT, $groupId);
            $queryBuilder->set('sc_group', $effectiveGroupId);
            $queryBuilder->set('group_uuid', null);
        }

        if ($userId !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
                )
            );
            if (!$allowGlobalGroups) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->gte(
                        'sc_group',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                );
            }
        }

        return $queryBuilder->executeStatement();
    }

    public function delete(int $id, ?int $userId = null): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->delete(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            );

        if ($userId !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
                )
            );
        }

        return $queryBuilder->executeStatement();
    }

    /**
     * @param array<int> $ids
     */
    public function deleteMultiple(array $ids, int $userId): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder->delete(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    public function updateSorting(int $id, int $userId, int $sorting): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
                )
            )
            ->set('sorting', $sorting)
            ->executeStatement();
    }

    /**
     * @param array<int> $ids
     */
    public function moveToGroup(array $ids, int $userId, int|string $groupId, bool $allowGlobalGroups = true): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)
                )
            );

        if (is_string($groupId)) {
            $queryBuilder->set('group_uuid', $groupId);
            $queryBuilder->set('sc_group', BookmarkService::GROUP_DEFAULT);
        } else {
            $effectiveGroupId = $allowGlobalGroups ? $groupId : max(BookmarkService::GROUP_DEFAULT, $groupId);
            $queryBuilder->set('sc_group', $effectiveGroupId);
            $queryBuilder->set('group_uuid', null);
        }

        return $queryBuilder->executeStatement();
    }

    public function findGroupsByUser(int $userId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);

        $result = $queryBuilder
            ->select('*')
            ->from(self::GROUP_TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT))
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery();

        $groups = [];
        while ($row = $result->fetchAssociative()) {
            $groups[] = $row;
        }

        return $groups;
    }

    public function findGroupByUuid(string $uuid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);
        $row = $queryBuilder
            ->select('*')
            ->from(self::GROUP_TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uuid', $queryBuilder->createNamedParameter($uuid))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? $row : null;
    }

    public function createGroup(int $userId, string $label): ?string
    {
        $uuid = (string)Uuid::v4();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);

        $maxSorting = $queryBuilder
            ->select('sorting')
            ->from(self::GROUP_TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT))
            )
            ->orderBy('sorting', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        $sorting = ($maxSorting !== false ? (int)$maxSorting : 0) + 1;

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);
        $affectedRows = $queryBuilder
            ->insert(self::GROUP_TABLE_NAME)
            ->values([
                'uuid' => $uuid,
                'userid' => $userId,
                'label' => $label,
                'sorting' => $sorting,
            ])
            ->executeStatement();

        return $affectedRows === 1 ? $uuid : null;
    }

    public function updateGroup(string $uuid, string $label): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);

        return $queryBuilder
            ->update(self::GROUP_TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uuid', $queryBuilder->createNamedParameter($uuid))
            )
            ->set('label', $label)
            ->executeStatement();
    }

    public function deleteGroup(string $uuid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);

        return $queryBuilder
            ->delete(self::GROUP_TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uuid', $queryBuilder->createNamedParameter($uuid))
            )
            ->executeStatement();
    }

    /**
     * @param list<string> $uuids
     */
    public function reorderGroups(array $uuids, int $userId): void
    {
        $sorting = 0;
        foreach ($uuids as $uuid) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::GROUP_TABLE_NAME);
            $queryBuilder
                ->update(self::GROUP_TABLE_NAME)
                ->where(
                    $queryBuilder->expr()->eq('uuid', $queryBuilder->createNamedParameter($uuid)),
                    $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT))
                )
                ->set('sorting', $sorting++)
                ->executeStatement();
        }
    }

    public function moveBookmarksFromGroupToDefault(string $uuid, int $userId): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('group_uuid', $queryBuilder->createNamedParameter($uuid)),
                $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT))
            )
            ->set('group_uuid', null)
            ->set('sc_group', BookmarkService::GROUP_DEFAULT)
            ->executeStatement();
    }
}
