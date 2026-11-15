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

namespace TYPO3\CMS\Linkvalidator\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for pages database queries.
 *
 * @internal not part of the TYPO3 Core API.
 */
class PagesRepository
{
    protected const TABLE = 'pages';

    /**
     * Check if rootline contains a hidden page (including current page)
     *
     * @param array $pageInfo Array with uid, title, hidden, extendToSubpages from pages table
     * @param int $recursionLevel starts with 0 on current page and gets incremented with each recursion (when moving up the rootline)
     * @return bool TRUE if rootline contains a hidden page, FALSE if not
     */
    public function doesRootLineContainHiddenPages(array $pageInfo, int $recursionLevel = 0): bool
    {
        $pid = (int)($pageInfo['pid'] ?? 0);
        $isHidden = (bool)($pageInfo['hidden'] ?? false);
        $extendToSubpages = (bool)($pageInfo['extendToSubpages'] ?? false);

        // is current page hidden ($recursionLevel=0) or is page in rootline hidden and $extendToSubpages=true
        if ($isHidden === true && ($extendToSubpages === true || $recursionLevel === 0)) {
            return true;
        }

        if ($pid === 0) {
            // has no (further) parent pages and no hidden pages detected
            return false;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('uid', 'pid', 'title', 'hidden', 'extendToSubpages')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($row !== false) {
            return $this->doesRootLineContainHiddenPages($row, ++$recursionLevel);
        }
        return false;
    }

    /**
     * Generates a list of page uids from $id. List does not include $id itself.
     * Deleted pages are excluded, hidden pages unless $considerHidden is set. The subpages of
     * hidden pages are included, unless the hidden page extends to its subpages.
     * Translated pages are included as well.
     *
     * Formerly called extGetTreeList
     *
     * @param int $id Start page id
     * @param int $depth Depth to traverse down the page tree.
     * @param string $permsClause Perms clause
     * @param bool $considerHidden Whether to consider hidden pages or not
     * @return int[] Returns the list of subpages (if any pages selected!)
     */
    public function getAllSubpagesForPage(
        int $id,
        int $depth,
        string $permsClause,
        bool $considerHidden = false
    ): array {
        if ($depth <= 0) {
            return [];
        }
        $permsClause = QueryHelper::stripLogicalOperatorPrefix($permsClause);
        $pages = GeneralUtility::makeInstance(PageDescendantsRepository::class)->findDescendants(
            [$id],
            $depth,
            0,
            $permsClause !== '' ? static fn(): string => $permsClause : null,
            [],
            ['hidden', 'extendToSubpages'],
        );
        $subPageIds = [];
        // Pages whose subpages were looked up, as their translations are subpages as well
        $parentPageIds = [$id];
        $skippedLevel = null;
        foreach ($pages as $page) {
            if ($skippedLevel !== null && $page['_LEVEL'] > $skippedLevel) {
                continue;
            }
            $skippedLevel = null;
            $isHidden = (bool)$page['hidden'];
            if (!$isHidden || $considerHidden) {
                $subPageIds[] = $page['uid'];
            }
            if ($isHidden && $page['extendToSubpages'] && !$considerHidden) {
                $skippedLevel = $page['_LEVEL'];
            } elseif ($page['_LEVEL'] < $depth) {
                $parentPageIds[] = $page['uid'];
            }
        }
        foreach (array_chunk($parentPageIds, 1000) as $parentPageIdsChunk) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            if (!$considerHidden) {
                $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
            }
            $constraints = [
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($parentPageIdsChunk, Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->gt('sys_language_uid', $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            ];
            if ($permsClause !== '') {
                $constraints[] = $permsClause;
            }
            $result = $queryBuilder
                ->select('uid')
                ->from(self::TABLE)
                ->where(...$constraints)
                ->executeQuery();
            while ($translatedPageId = $result->fetchOne()) {
                $subPageIds[] = (int)$translatedPageId;
            }
        }
        return $subPageIds;
    }

    /**
     * Add page translations to list of pages
     *
     * Formerly called addPageTranslationsToPageList
     *
     * @param int $currentPage
     * @param string $permsClause
     * @param bool $considerHiddenPages
     * @param int[] $limitToLanguageIds
     * @return int[]
     */
    public function getTranslationForPage(
        int $currentPage,
        string $permsClause,
        bool $considerHiddenPages,
        array $limitToLanguageIds = []
    ): array {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if (!$considerHiddenPages) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }
        $constraints = [
            $queryBuilder->expr()->eq(
                'l10n_parent',
                $queryBuilder->createNamedParameter($currentPage, Connection::PARAM_INT)
            ),
        ];
        if (!empty($limitToLanguageIds)) {
            $constraints[] = $queryBuilder->expr()->in(
                'sys_language_uid',
                $queryBuilder->createNamedParameter($limitToLanguageIds, Connection::PARAM_INT_ARRAY)
            );
        }
        if ($permsClause) {
            $constraints[] = QueryHelper::stripLogicalOperatorPrefix($permsClause);
        }

        $result = $queryBuilder
            ->select('uid', 'title', 'hidden')
            ->from(self::TABLE)
            ->where(...$constraints)
            ->executeQuery();

        $translatedPages = [];
        while ($row = $result->fetchAssociative()) {
            $translatedPages[] = (int)$row['uid'];
        }

        return $translatedPages;
    }
}
