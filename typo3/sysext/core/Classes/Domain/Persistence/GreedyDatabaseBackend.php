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

namespace TYPO3\CMS\Core\Domain\Persistence;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetches all records of a single table of one / multiple PID(s)
 * in one DB query, and stores them in a runtime cache.
 *
 * The records are neither grouped nor do we care about
 * - sorting
 * - limit / offset
 * - or BE User permissions.
 *
 * It is "greedy" because it is meant to do simple query
 * in a fast way, to reduce overlays on a "per record" basis.
 *
 * The records are fetched depending on
 * - fe_group permissions
 * - language (incl. overlays etc)
 * - workspace
 * - visibility restrictions (starttime / endtime / hidden / deleted)
 *
 * The class returns an array and does not handle objects,
 * as it is very "lowlevel" and thus: internal.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class GreedyDatabaseBackend
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        protected FrontendInterface $runtimeCache,
        protected RecordAccessVoter $recordAccessVoter,
        protected ConnectionPool $connectionPool,
        protected PageRepository $pageRepository
    ) {}

    public function getRows(string $tableName, array $uids, Context $context): array
    {
        $uids = array_values(array_unique(array_map(static fn(int|string $uid): int => (int)$uid, $uids)));
        $uidsToResolve = array_values(array_filter(
            $uids,
            fn(int $uid): bool => !$this->isResolvableFromCache($tableName, $uid, $context)
        ));
        if ($uidsToResolve !== []) {
            $this->setCache($tableName, $uidsToResolve, $context, $this->getRowsFromDatabase($tableName, $uidsToResolve, $context));
        }
        return $this->handleOverlays(
            $this->getRowsFromCache($tableName, $uids, $context),
            $tableName,
            $context
        );
    }

    /**
     * The greedy result sets are stored per pid, and each uid points to the pid its row has been
     * found on. A uid is answerable from the runtime cache if that pointer exists and the row set
     * of the pid it points to is still cached. Requested uids without an accessible row are
     * pointed to "false", so they do not trigger a database query over and over again.
     */
    protected function isResolvableFromCache(string $tableName, int $uid, Context $context): bool
    {
        $pointerCacheIdentifier = $this->createUidPointerCacheIdentifier($tableName, $uid, $context);
        if (!$this->runtimeCache->has($pointerCacheIdentifier)) {
            return false;
        }
        $pid = $this->runtimeCache->get($pointerCacheIdentifier);
        return $pid === false || $this->runtimeCache->has($this->createRowsCacheIdentifier($tableName, (int)$pid, $context));
    }

    /**
     * Splits the greedy result set into one cache entry per pid, and creates a lightweight
     * pointer from each uid to the pid its row belongs to.
     *
     * The rows of a pid are complete: the greedy query fetches all accessible records of the pids
     * of the given uids, so a pid based cache entry can be reused for any later request of any
     * record on that pid - no matter which uids that request combines.
     *
     * Example: Having 1000 records on the same pid with each having a relation to
     * a file reference. When RecordFactory is used to resolve all of these 1000 records
     * at a time, each of these 1000 relations would produce a cache entry with 1000
     * file reference database rows (1000*1000 = 1.000.000 database rows).
     *
     * Instead, only 1 cache entry is created with 1000 database rows and in addition
     * 1000 lightweight cache identifier pointers, pointing to that pid.
     */
    protected function setCache(string $tableName, array $uids, Context $context, array $allRows): void
    {
        $rowsByPid = [];
        foreach ($allRows as $row) {
            $rowsByPid[(int)$row['pid']][] = $row;
        }
        foreach ($rowsByPid as $pid => $rowsOfPid) {
            $this->runtimeCache->set($this->createRowsCacheIdentifier($tableName, $pid, $context), $rowsOfPid);
            foreach ($rowsOfPid as $row) {
                $this->runtimeCache->set($this->createUidPointerCacheIdentifier($tableName, (int)$row['uid'], $context), $pid);
            }
        }
        $resolvedUids = array_map(static fn(array $row): int => (int)$row['uid'], $allRows);
        foreach (array_diff($uids, $resolvedUids) as $uid) {
            // Remember that this uid has no accessible row in the current context.
            $this->runtimeCache->set($this->createUidPointerCacheIdentifier($tableName, $uid, $context), false);
        }
    }

    /**
     * Collects the rows of the given uids from the pid based cache entries. The uids may be
     * spread over any number of pids, so all pointed to result sets are taken into account.
     * The rows are returned in the order of the given uids.
     */
    protected function getRowsFromCache(string $tableName, array $uids, Context $context): array
    {
        $pids = [];
        foreach ($uids as $uid) {
            $pointerCacheIdentifier = $this->createUidPointerCacheIdentifier($tableName, $uid, $context);
            if (!$this->runtimeCache->has($pointerCacheIdentifier)) {
                continue;
            }
            $pid = $this->runtimeCache->get($pointerCacheIdentifier);
            if ($pid !== false) {
                $pids[(int)$pid] = true;
            }
        }
        $rowsByUid = [];
        foreach (array_keys($pids) as $pid) {
            $rowsCacheIdentifier = $this->createRowsCacheIdentifier($tableName, $pid, $context);
            if (!$this->runtimeCache->has($rowsCacheIdentifier)) {
                continue;
            }
            foreach ($this->runtimeCache->get($rowsCacheIdentifier) as $row) {
                $rowsByUid[(int)$row['uid']] = $row;
            }
        }
        $rows = [];
        foreach ($uids as $uid) {
            if (isset($rowsByUid[$uid])) {
                $rows[] = $rowsByUid[$uid];
            }
        }
        return $rows;
    }

    protected function getRowsFromDatabase(string $tableName, array $uids, Context $context): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder
            ->select('*')
            ->from($tableName);
        // @todo: consider a context-based query restriction container here!
        // @todo: we should not remove the restrictions but rather add them based on the given Context
        /** @var DefaultRestrictionContainer $restrictions */
        $restrictions = $queryBuilder->getRestrictions();
        $visibilityAspect = $context->getAspect('visibility');
        if ($visibilityAspect->includeHidden()) {
            $restrictions->removeByType(HiddenRestriction::class);
        }
        if ($visibilityAspect->includeDeletedRecords()) {
            $restrictions->removeByType(DeletedRestriction::class);
        }
        if ($visibilityAspect->includeScheduledRecords()) {
            $restrictions->removeByType(StartTimeRestriction::class);
            $restrictions->removeByType(EndTimeRestriction::class);
        }
        if ($context->hasAspect('frontend.user')) {
            $groupIds = $context->getAspect('frontend.user')->getGroupIds();
            $restrictions->add(GeneralUtility::makeInstance(FrontendGroupRestriction::class, $groupIds));
        }
        // Workspace Restriction is never added
        $restrictions->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $context->getAspect('workspace')->getId()));

        // Subselect is doing: give me the PID of the given UIDs
        // So we can get a greedy query for all records of these PIDs
        $queryBuilderForSubselect = $queryBuilder->getConnection()->createQueryBuilder();
        // Subselect must use same restrictions as main query
        $queryBuilderForSubselect->setRestrictions($restrictions);
        $queryBuilderForSubselect
            ->select('pid')
            ->distinct()
            ->from($tableName)
            ->where(
                $queryBuilderForSubselect->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
                )
            );

        // Inject the subselect in the WHERE part of the main query
        $queryBuilder->where(
            $queryBuilder->expr()->comparison(
                $queryBuilder->quoteIdentifier('pid'),
                'IN',
                '(' . $queryBuilderForSubselect->getSQL() . ')'
            )
        );

        $allRows = $queryBuilder->executeQuery()->fetchAllAssociative();
        return $allRows;
    }

    protected function handleOverlays(array $rows, string $dbTable, Context $context): array
    {
        $pageRepository = $this->pageRepository->withContext($context);
        $finalRows = [];
        foreach ($rows as $row) {
            $pageRepository->versionOL($dbTable, $row);
            if ($row === false) {
                continue;
            }
            $row = $pageRepository->getLanguageOverlay($dbTable, $row);
            if ($row === null) {
                continue;
            }
            // Check fe_group, hidden, starttime, endtime etc.
            if (!$this->recordAccessVoter->accessGranted($dbTable, $row, $context)) {
                continue;
            }
            $finalRows[] = $row;
        }
        return $finalRows;
    }

    /**
     * Identifier of the cache entry holding all accessible rows of one pid.
     */
    protected function createRowsCacheIdentifier(string $tableName, int $pid, Context $context): string
    {
        return $this->createRuntimeCacheIdentifier($tableName, 'pid-' . $pid, $context);
    }

    /**
     * Identifier of the pointer entry telling on which pid the row of a uid has been found,
     * or "false" if the uid has no accessible row.
     */
    protected function createUidPointerCacheIdentifier(string $tableName, int $uid, Context $context): string
    {
        return $this->createRuntimeCacheIdentifier($tableName, 'uid-' . $uid, $context);
    }

    protected function createRuntimeCacheIdentifier(string $tableName, string $entryKey, Context $context): string
    {
        $cacheIdentifier = $tableName . '-' . $entryKey . '-';
        $cacheIdentifier .= $context->getAspect('workspace')->getId() . '-';
        /** @var LanguageAspect $languageAspect */
        $languageAspect = $context->getAspect('language');
        $cacheIdentifier .= $languageAspect->getId() . '-' . $languageAspect->getOverlayType() . '-' . md5(implode('_', $languageAspect->getFallbackChain())) . '-';
        /** @var VisibilityAspect $visibilityAspect */
        $visibilityAspect = $context->getAspect('visibility');
        $cacheIdentifier .= $visibilityAspect->includeHiddenPages() ? '1' : '0';
        $cacheIdentifier .= $visibilityAspect->includeHiddenContent() ? '1' : '0';
        $cacheIdentifier .= $visibilityAspect->includeScheduledRecords() ? '1' : '0';
        $cacheIdentifier .= $visibilityAspect->includeDeletedRecords() ? '1' : '0';

        /** @var DateTimeAspect $dateAspect */
        $dateAspect = $context->getAspect('date');
        $cacheIdentifier .= '-' . $dateAspect->get('timestamp');

        /** @var UserAspect $userAspect */
        $userAspect = $context->getAspect('frontend.user');
        $groupIds = $userAspect->getGroupIds();
        $cacheIdentifier .= '-' . implode('_', $groupIds);

        return 'greedy_database_backend_' . hash('xxh3', $cacheIdentifier);
    }
}
