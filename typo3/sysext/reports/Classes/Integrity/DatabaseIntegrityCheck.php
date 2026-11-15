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

namespace TYPO3\CMS\Reports\Integrity;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class holds functions used by the TYPO3 backend for the RecordStatistics Service
 * of the database.
 *
 * @internal not part of TYPO3 Core API
 */
#[Autoconfigure(public: true)]
class DatabaseIntegrityCheck
{
    private const int MAX_PARAMETERS_PER_QUERY = 1000;

    /**
     * @var array Will hold id/rec pairs from genTree()
     */
    protected array $pageIdArray = [];

    /**
     * @var array Will hold id/rec pairs from genTree() that are not default language
     */
    protected array $pageTranslatedPageIDArray = [];

    protected array $recStats = [
        'all_valid' => [],
        'published_versions' => [],
        'deleted' => [],
    ];

    protected array $lRecords = [];

    /**
     * @var array<string, array<int, int>> Number of records by page id, by table
     */
    private array $recordsPerPage = [];

    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Generates a list of Page-uid's that corresponds to the tables in the tree.
     * This list should ideally include all records in the pages-table.
     *
     * All page records - including deleted pages, translations and workspace versions -
     * are fetched with one query, the tree is built from them in PHP. Each page connected
     * to the given page by its pid or its live page (t3ver_oid) is collected once, so a
     * broken page tree with cycling references does not lead to an endless recursion.
     *
     * @param int $theID a pid (page-record id) from which to start making the tree
     */
    public function genTree(int $theID): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid', 'pid', 't3ver_oid', 't3ver_wsid', 'title', 'doktype', 'deleted', 'hidden', 'sys_language_uid')
            ->from('pages')
            ->orderBy('sorting')
            ->addOrderBy('uid')
            ->executeQuery();
        $subpages = [];
        $versions = [];
        while ($row = $result->fetchAssociative()) {
            $subpages[(int)$row['pid']][] = $row;
            if ((int)$row['t3ver_oid'] > 0) {
                $versions[(int)$row['t3ver_oid']][] = $row;
            }
        }

        // Each page is followed by its subpages and then its versions
        $visited = [$theID => true];
        $stack = array_reverse([...$subpages[$theID] ?? [], ...$versions[$theID] ?? []]);
        while ($stack !== []) {
            $row = array_pop($stack);
            $pageId = (int)$row['uid'];
            if (isset($visited[$pageId])) {
                continue;
            }
            $visited[$pageId] = true;
            $this->registerPage($row);
            foreach (array_reverse([...$subpages[$pageId] ?? [], ...$versions[$pageId] ?? []]) as $child) {
                if (!isset($visited[(int)$child['uid']])) {
                    $stack[] = $child;
                }
            }
        }
    }

    private function registerPage(array $row): void
    {
        $pageId = (int)$row['uid'];
        if (isset($this->recStats['all_valid']['pages'][$pageId])) {
            return;
        }
        if ((int)$row['sys_language_uid'] === LanguageMarker::DEFAULT_LANGUAGE) {
            $this->pageIdArray[$pageId] = $row;
        } else {
            $this->pageTranslatedPageIDArray[$pageId] = $row;
        }
        $this->recStats['all_valid']['pages'][$pageId] = $pageId;
        if ($row['deleted']) {
            $this->recStats['deleted']['pages'][$pageId] = $pageId;
        }
        $this->recStats['hidden'] ??= 0;
        if ($row['hidden']) {
            $this->recStats['hidden']++;
        }
        $this->recStats['doktype'][$row['doktype']] ??= 0;
        $this->recStats['doktype'][$row['doktype']]++;
    }

    /**
     * Fills $this->lRecords with the records from all tc-tables that are not attached to a PID in the pid-list.
     *
     * The page ids are compared in PHP, as the list may contain more page ids than a
     * database accepts as parameters of one statement.
     *
     * @param array $pageIds list of pid's (page-record uid's). This list is probably made by genTree()
     */
    public function lostRecords(array $pageIds): array
    {
        $pageIds = array_fill_keys(array_map(intval(...), $pageIds), true);
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $lostPageIds = array_keys(array_diff_key($this->countRecordsPerPage($table, false), $pageIds));
            foreach (array_chunk($lostPageIds, self::MAX_PARAMETERS_PER_QUERY) as $lostPageIdsChunk) {
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()->removeAll();
                $queryResult = $queryBuilder
                    ->select('*')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->in(
                            'pid',
                            $queryBuilder->createNamedParameter($lostPageIdsChunk, Connection::PARAM_INT_ARRAY)
                        )
                    )
                    ->executeQuery();
                while ($row = $queryResult->fetchAssociative()) {
                    $recordId = (int)$row['uid'];
                    $this->lRecords[$table][$recordId] = [
                        'uid' => $recordId,
                        'pid' => $row['pid'],
                        'title' => strip_tags(BackendUtility::getRecordTitle($table, $row)),
                    ];
                }
            }
        }
        return $this->lRecords;
    }

    /**
     * Counts records from $GLOBALS['TCA']-tables that ARE attached to an existing page.
     *
     * @param array $pageIds list of pid's (page-record uid's). This list is probably made by genTree()
     * @return array an array with the number of records from all $GLOBALS['TCA']-tables that are attached to a PID in the pid-list.
     */
    public function countRecords(array $pageIds): array
    {
        $pageIds = array_fill_keys(array_map(intval(...), $pageIds), true);
        $list = [];
        $list_n = [];
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $count = array_sum(array_intersect_key($this->countRecordsPerPage($table, false), $pageIds));
            if ($count) {
                $list[$table] = $count;
            }
            // same count excluding all deleted records
            $count = array_sum(array_intersect_key($this->countRecordsPerPage($table, true), $pageIds));
            if ($count) {
                $list_n[$table] = $count;
            }
        }
        return ['all' => $list, 'non_deleted' => $list_n];
    }

    /**
     * @return array<int, int> Number of records by page id
     */
    private function countRecordsPerPage(string $table, bool $excludeDeleted): array
    {
        $cacheIdentifier = $table . ($excludeDeleted ? '-non-deleted' : '-all');
        if (isset($this->recordsPerPage[$cacheIdentifier])) {
            return $this->recordsPerPage[$cacheIdentifier];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        if ($excludeDeleted) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $result = $queryBuilder
            ->select('pid')
            ->addSelectLiteral($queryBuilder->expr()->count('uid', 'records'))
            ->from($table)
            ->groupBy('pid')
            ->executeQuery();
        $recordsPerPage = [];
        while ($row = $result->fetchAssociative()) {
            $recordsPerPage[(int)$row['pid']] = (int)$row['records'];
        }
        return $this->recordsPerPage[$cacheIdentifier] = $recordsPerPage;
    }

    public function getPageIdArray(): array
    {
        return $this->pageIdArray;
    }

    public function getPageTranslatedPageIDArray(): array
    {
        return $this->pageTranslatedPageIDArray;
    }

    public function getRecStats(): array
    {
        return $this->recStats;
    }
}
