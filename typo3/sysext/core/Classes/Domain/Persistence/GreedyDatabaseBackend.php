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
        protected ConnectionPool $connectionPool
    ) {}

    public function getRows(string $tableName, array $uids, Context $context): array
    {
        // Check runtime cache first
        // @todo: the runtime cache needs to be much more sophisticated
        //        as it needs to understand that a DB query for ID 4,5 has been made, because
        //        they have been fetched with IDs 2,3 in the call before.
        $cacheIdentifier = $this->createRuntimeCacheIdentifier($tableName, $uids, $context);
        if ($this->runtimeCache->has($cacheIdentifier)) {
            $allRows = $this->runtimeCache->get($cacheIdentifier);
        } else {
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
            $queryBuilderForSubselect
                ->select('pid')
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
            $this->runtimeCache->set($cacheIdentifier, $allRows);
        }

        return $this->handleOverlays(
            // Only use the records from the given UIDs
            array_filter($allRows, static fn(array $row) => in_array((int)$row['uid'], $uids, true)),
            $tableName,
            $context
        );
    }

    protected function handleOverlays(array $rows, string $dbTable, Context $context): array
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
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

    protected function createRuntimeCacheIdentifier(string $tableName, array $uids, Context $context): string
    {
        sort($uids);
        $cacheIdentifier = $tableName . '-' . md5(implode('_', $uids)) . '-';
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
