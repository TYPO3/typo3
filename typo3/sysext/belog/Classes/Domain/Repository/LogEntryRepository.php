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

namespace TYPO3\CMS\Belog\Domain\Repository;

use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Log\LogLevel as Typo3LogLevel;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sys log entry repository
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class LogEntryRepository
{
    public function findByUid($uid): ?LogEntry
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $row = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->fetchAssociative();
        return $row ? LogEntry::createFromDatabaseRecord($row) : null;
    }

    protected function createQuery(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->orderBy('uid', 'DESC');
        return $queryBuilder;
    }

    /**
     * Finds all log entries that match all given constraints.
     *
     * @return array<LogEntry>
     */
    public function findByConstraint(Constraint $constraint): array
    {
        $query = $this->createQuery();
        $queryConstraints = $this->createQueryConstraints($query, $constraint);
        $stmt = $query
            ->where(...$queryConstraints)
            ->setMaxResults($constraint->getNumber())
            ->executeQuery();

        $result = [];
        while ($row = $stmt->fetchAssociative()) {
            $result[] = LogEntry::createFromDatabaseRecord($row);
        }
        return $result;
    }

    /**
     * Create an array of query constraints from constraint object
     */
    protected function createQueryConstraints(QueryBuilder $query, Constraint $constraint): array
    {
        // User / group handling
        $queryConstraints = $this->addUsersAndGroupsToQueryConstraints($constraint, $query);
        // Workspace
        if ($constraint->getWorkspaceUid() !== -99) {
            $queryConstraints[] = $query->expr()->eq('workspace', $query->createNamedParameter($constraint->getWorkspaceUid(), Connection::PARAM_INT));
        }
        // Channel
        if ($channel = $constraint->getChannel()) {
            $queryConstraints[] = $query->expr()->eq('channel', $query->createNamedParameter($channel));
        }
        // Level
        if ($level = $constraint->getLevel()) {
            $queryConstraints[] = $query->expr()->in('level', $query->createNamedParameter(Typo3LogLevel::atLeast($level), Connection::PARAM_STR_ARRAY));
        }
        // Start / endtime handling: The timestamp calculation was already done
        // in the controller, since we need those calculated values in the view as well.
        $queryConstraints[] = $query->expr()->gte('tstamp', $query->createNamedParameter($constraint->getStartTimestamp(), Connection::PARAM_INT));
        $queryConstraints[] = $query->expr()->lt('tstamp', $query->createNamedParameter($constraint->getEndTimestamp(), Connection::PARAM_INT));
        // Page and level constraint if in page context
        $constraint = $this->addPageTreeConstraintsToQuery($constraint, $query);
        if ($constraint) {
            $queryConstraints[] = $constraint;
        }
        return $queryConstraints;
    }

    /**
     * Adds constraints for the page(s) to the query; this could be one single page or a whole subtree beneath a given
     * page.
     */
    protected function addPageTreeConstraintsToQuery(
        Constraint $constraint,
        QueryBuilder $query,
    ): ?string {
        $pageIds = [];
        // Check if we should get a whole tree of pages and not only a single page
        if ($constraint->getDepth() > 0) {
            $repository = GeneralUtility::makeInstance(PageTreeRepository::class);
            $repository->setAdditionalWhereClause($GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW));
            $pages = $repository->getFlattenedPages([$constraint->getPageId()], $constraint->getDepth());
            foreach ($pages as $page) {
                $pageIds[] = (int)$page['uid'];
            }
        }
        if (!empty($constraint->getPageId())) {
            $pageIds[] = $constraint->getPageId();
        }
        if (!empty($pageIds)) {
            return $query->expr()->in('event_pid', $query->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY));
        }
        return null;
    }

    /**
     * Adds users and groups to the query constraints.
     */
    protected function addUsersAndGroupsToQueryConstraints(
        Constraint $constraint,
        QueryBuilder $query
    ): array {
        $userOrGroup = $constraint->getUserOrGroup();
        if ($userOrGroup === '') {
            return [];
        }
        $queryConstraints = [];
        // Constraint for a group
        if (str_starts_with($userOrGroup, 'gr-')) {
            $groupId = (int)substr($userOrGroup, 3);
            $groupResolver = GeneralUtility::makeInstance(GroupResolver::class);
            $userIds = $groupResolver->findAllUsersInGroups([$groupId], 'be_groups', 'be_users');
            if (!empty($userIds)) {
                $userIds = array_column($userIds, 'uid');
                $userIds = array_map(intval(...), $userIds);
                $queryConstraints[] = $query->expr()->in('userid', $query->createNamedParameter($userIds, Connection::PARAM_INT_ARRAY));
            } else {
                // If there are no group members -> use -1 as constraint to not find anything
                $queryConstraints[] = $query->expr()->eq('userid', $query->createNamedParameter(-1, Connection::PARAM_INT));
            }
        } elseif (str_starts_with($userOrGroup, 'us-')) {
            $queryConstraints[] = $query->expr()->in('userid', $query->createNamedParameter((int)substr($userOrGroup, 3), Connection::PARAM_INT));
        } elseif ($userOrGroup === '-1') {
            $queryConstraints[] = $query->expr()->in('userid', $query->createNamedParameter((int)$GLOBALS['BE_USER']->user['uid'], Connection::PARAM_INT));
        }
        return $queryConstraints;
    }

    /**
     * Deletes all messages which have the same message details
     */
    public function deleteByMessageDetails(LogEntry $logEntry): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_log');
        $constraints = [];
        $constraints[] = $queryBuilder->expr()->eq('details', $queryBuilder->createNamedParameter($logEntry->getDetails()));
        // If the detailsNo is 11 or 12 we got messages that are heavily using placeholders. In this case
        // we need to compare both the message and the actual log data to not remove too many log entries.
        if (in_array($logEntry->getDetailsNumber(), [11, 12], true)) {
            $constraints[] = $queryBuilder->expr()->eq('log_data', $queryBuilder->createNamedParameter($logEntry->getLogDataRaw()));
        }
        return (int)$queryBuilder->delete('sys_log')
            ->where(...$constraints)
            ->executeStatement();
    }

    public function getUsedChannels(): array
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_log');

        $channels = $conn->createQueryBuilder()
            ->select('channel')
            ->distinct()
            ->from('sys_log')
            ->orderBy('channel')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_combine($channels, $channels);
    }

    public function getUsedLevels(): array
    {
        static $allLevels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];

        $conn = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_log');

        $levels = $conn->createQueryBuilder()
            ->select('level')
            ->distinct()
            ->from('sys_log')
            ->executeQuery()
            ->fetchFirstColumn();

        $levelsUsed = array_intersect($allLevels, $levels);

        return array_combine($levelsUsed, $levelsUsed);
    }
}
