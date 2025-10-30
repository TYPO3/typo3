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

    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory
    ) {}

    /**
     * Generates a list of Page-uid's that corresponds to the tables in the tree.
     * This list should ideally include all records in the pages-table.
     *
     * @param int $theID a pid (page-record id) from which to start making the tree
     * @param bool $versions Internal variable, don't set from outside!
     */
    public function genTree(int $theID, bool $versions = false): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid', 'title', 'doktype', 'deleted', 'hidden', 'sys_language_uid')
            ->from('pages')
            ->orderBy('sorting');
        if ($versions) {
            $queryBuilder->addSelect('t3ver_wsid');
            $queryBuilder->where(
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($theID, Connection::PARAM_INT))
            );
        } else {
            $queryBuilder->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($theID, Connection::PARAM_INT))
            );
        }
        $result = $queryBuilder->executeQuery();
        // Traverse the records selected
        while ($row = $result->fetchAssociative()) {
            $newID = $row['uid'];
            // Register various data for this item:
            if ($row['sys_language_uid'] === 0) {
                $this->pageIdArray[$newID] = $row;
            } else {
                $this->pageTranslatedPageIDArray[$newID] = $row;
            }
            $this->recStats['all_valid']['pages'][$newID] = $newID;
            if ($row['deleted']) {
                $this->recStats['deleted']['pages'][$newID] = $newID;
            }

            if (!isset($this->recStats['hidden'])) {
                $this->recStats['hidden'] = 0;
            }

            if ($row['hidden']) {
                $this->recStats['hidden']++;
            }

            $this->recStats['doktype'][$row['doktype']] ??= 0;
            $this->recStats['doktype'][$row['doktype']]++;
            // Add sub pages:
            $this->genTree($newID);
            // If versions are included in the tree, add those now:
            $this->genTree($newID, true);
        }
    }

    /**
     * Fills $this->lRecords with the records from all tc-tables that are not attached to a PID in the pid-list.
     *
     * @param array $pageIds list of pid's (page-record uid's). This list is probably made by genTree()
     */
    public function lostRecords(array $pageIds): array
    {
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $queryResult = $queryBuilder
                ->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->notIn(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
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
        $list = [];
        $list_n = [];
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $pageIdsForTable = $pageIds;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $count = $queryBuilder->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery()
                ->fetchOne();
            if ($count) {
                $list[$table] = $count;
            }

            // same query excluding all deleted records
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $count = $queryBuilder->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIdsForTable, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery()
                ->fetchOne();
            if ($count) {
                $list_n[$table] = $count;
            }
        }
        return ['all' => $list, 'non_deleted' => $list_n];
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
