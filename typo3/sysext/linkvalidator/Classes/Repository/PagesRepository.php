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
     * Check if rootline contains a hidden page
     *
     * @param array $pageInfo Array with uid, title, hidden, extendToSubpages from pages table
     * @return bool TRUE if rootline contains a hidden page, FALSE if not
     */
    public function doesRootLineContainHiddenPages(array $pageInfo): bool
    {
        $pid = (int)($pageInfo['pid'] ?? 0);
        if ($pid === 0) {
            return false;
        }
        $isHidden = (bool)($pageInfo['hidden']);
        $extendToSubpages = (bool)($pageInfo['extendToSubpages']);

        if ($extendToSubpages === true && $isHidden === true) {
            return true;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('uid', 'title', 'hidden', 'extendToSubpages')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($row !== false) {
            return $this->doesRootLineContainHiddenPages($row);
        }
        return false;
    }

    /**
     * Generates a list of page uids from $id. List does not include $id itself.
     * The only pages excluded from the list are deleted pages.
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
        $subPageIds = [];
        if ($depth === 0) {
            return $subPageIds;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'title', 'hidden', 'extendToSubpages')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                ),
                QueryHelper::stripLogicalOperatorPrefix($permsClause)
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $subpageId = (int)$row['uid'];
            $isHidden = (bool)$row['hidden'];
            if (!$isHidden || $considerHidden) {
                $subPageIds[] = $subpageId;
            }
            if ($depth > 1 && (!($isHidden && $row['extendToSubpages'] == 1) || $considerHidden)) {
                $subPageIds = array_merge($subPageIds, $this->getAllSubpagesForPage(
                    $subpageId,
                    $depth - 1,
                    $permsClause,
                    $considerHidden
                ));
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
                $queryBuilder->createNamedParameter($currentPage, \PDO::PARAM_INT)
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
