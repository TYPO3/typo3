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

namespace TYPO3\CMS\Core\Authentication;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\Event\AfterGroupsResolvedEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A provider for resolving fe_groups / be_groups, including nested sub groups.
 *
 * When fetching subgroups, the current group (parent group) is handed in recursive.
 * Duplicates are suppressed: If a sub group is including in multiple parent groups,
 * it will be resolved only once.
 *
 * @internal this is not part of TYPO3 Core API.
 */
class GroupResolver
{
    protected EventDispatcherInterface $eventDispatcher;
    protected string $sourceTable = '';
    protected string $sourceField = 'usergroup';
    protected string $recursiveSourceField = 'subgroup';

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Fetch all group records for a given user recursive.
     *
     * Note order is important: A user with main groups "1,2", where 1 has sub group 3,
     * results in "3,1,2" as record list array - sub groups are listed before the group
     * that includes the sub group.
     *
     * @param array $userRecord Used for context in PSR-14 event
     * @param string $sourceTable The database table to look up: be_groups / fe_groups depending on context
     * @return array List of group records. Note the ordering note above.
     */
    public function resolveGroupsForUser(array $userRecord, string $sourceTable): array
    {
        $this->sourceTable = $sourceTable;
        $originalGroupIds = GeneralUtility::intExplode(',', $userRecord[$this->sourceField] ?? '', true);
        $resolvedGroups = $this->fetchGroupsRecursive($originalGroupIds);
        $event = $this->eventDispatcher->dispatch(new AfterGroupsResolvedEvent($sourceTable, $resolvedGroups, $originalGroupIds, $userRecord));
        return $event->getGroups();
    }

    /**
     * Load a list of group uids, and take into account if groups have been loaded before.
     *
     * @param int[] $groupIds
     * @param array $processedGroupIds
     * @return array
     */
    protected function fetchGroupsRecursive(array $groupIds, array $processedGroupIds = []): array
    {
        if (empty($groupIds)) {
            return [];
        }
        $foundGroups = $this->fetchRowsFromDatabase($groupIds);
        $validGroups = [];
        foreach ($groupIds as $groupId) {
            // Database did not find the record
            if (!is_array($foundGroups[$groupId])) {
                continue;
            }
            // Record was already processed, continue to avoid adding this group again
            if (in_array($groupId, $processedGroupIds, true)) {
                continue;
            }
            // Add sub groups first
            $subgroupIds = GeneralUtility::intExplode(',', $foundGroups[$groupId][$this->recursiveSourceField] ?? '', true);
            if (!empty($subgroupIds)) {
                $subgroups = $this->fetchGroupsRecursive($subgroupIds, array_merge($processedGroupIds, [$groupId]));
                $validGroups = array_merge($validGroups, $subgroups);
            }
            // Add main group after sub groups have been added
            $validGroups[] = $foundGroups[$groupId];
        }
        return $validGroups;
    }

    /**
     * Does the database query. Does not care about ordering, this is done by caller.
     *
     * @param array $groupIds
     * @return array Full records with record uid as key
     */
    protected function fetchRowsFromDatabase(array $groupIds): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->sourceTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->sourceTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $groupIds,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->execute();
        $groups = [];
        while ($row = $result->fetch()) {
            $groups[(int)$row['uid']] = $row;
        }
        return $groups;
    }
}
