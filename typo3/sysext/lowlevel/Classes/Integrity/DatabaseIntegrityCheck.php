<?php

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

namespace TYPO3\CMS\Lowlevel\Integrity;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class holds functions used by the TYPO3 backend to check the integrity
 * of the database (The DBint module, 'lowlevel' extension)
 *
 * Depends on \TYPO3\CMS\Core\Database\RelationHandler
 *
 * @TODO: Need to really extend this class when the DataHandler library has been
 * @TODO: updated and the whole API is better defined. There are some known bugs
 * @TODO: in this library. Further it would be nice with a facility to not only
 * @TODO: analyze but also clean up!
 * @see \TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController::relationsAction()
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

    /**
     * @var array Statistics
     */
    protected array $recStats = [
        'all_valid' => [],
        'published_versions' => [],
        'deleted' => [],
    ];

    protected array $lRecords = [];
    protected string $lostPagesList = '';

    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory
    ) {}

    public function getPageTranslatedPageIDArray(): array
    {
        return $this->pageTranslatedPageIDArray;
    }

    /**
     * Generates a list of Page-uid's that corresponds to the tables in the tree.
     * This list should ideally include all records in the pages-table.
     *
     * @param int $theID a pid (page-record id) from which to start making the tree
     * @param bool $versions Internal variable, don't set from outside!
     */
    public function genTree($theID, $versions = false)
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
     * @param string $pid_list list of pid's (page-record uid's). This list is probably made by genTree()
     */
    public function lostRecords($pid_list): void
    {
        $this->lostPagesList = '';
        $pageIds = GeneralUtility::intExplode(',', $pid_list);
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $pageIdsForTable = $pageIds;
            // Remove preceding "-1," for non-versioned tables
            if (!$schema->isWorkspaceAware()) {
                $pageIdsForTable = array_combine($pageIdsForTable, $pageIdsForTable);
                unset($pageIdsForTable[-1]);
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $queryResult = $queryBuilder
                ->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->notIn(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIdsForTable, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery();
            $lostIdList = [];
            while ($row = $queryResult->fetchAssociative()) {
                $this->lRecords[$table][$row['uid']] = [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'title' => strip_tags(BackendUtility::getRecordTitle($table, $row)),
                ];
                $lostIdList[] = $row['uid'];
            }
            if ($table === 'pages') {
                $this->lostPagesList = implode(',', $lostIdList);
            }
        }
    }

    /**
     * Fixes lost record from $table with uid $uid by setting the PID to zero.
     * If there is a disabled column for the record that will be set as well.
     *
     * @param string $table Database tablename
     * @param int $uid The uid of the record which will have the PID value set to 0 (zero)
     * @return bool TRUE if done.
     */
    public function fixLostRecord(string $table, $uid): bool
    {
        if ($table === '') {
            return false;
        }
        if (!$this->tcaSchemaFactory->has($table)) {
            return false;
        }
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            return false;
        }
        if (!is_array($this->lRecords[$table][$uid])) {
            return false;
        }
        $updateFields = [
            'pid' => 0,
        ];
        // If possible a lost record restored is hidden as default
        $schema = $this->tcaSchemaFactory->get($table);
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $disableFieldName = $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
            $updateFields[$disableFieldName] = 1;
        }
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->update($table, $updateFields, ['uid' => (int)$uid]);
        return true;
    }

    /**
     * Counts records from $GLOBALS['TCA']-tables that ARE attached to an existing page.
     *
     * @param string $pid_list list of pid's (page-record uid's). This list is probably made by genTree()
     * @return array an array with the number of records from all $GLOBALS['TCA']-tables that are attached to a PID in the pid-list.
     */
    public function countRecords($pid_list): array
    {
        $list = [];
        $list_n = [];
        $pageIds = GeneralUtility::intExplode(',', $pid_list);
        if (!empty($pageIds)) {
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
        }
        return ['all' => $list, 'non_deleted' => $list_n];
    }

    public function getPageIdArray(): array
    {
        return $this->pageIdArray;
    }

    public function getRecStats(): array
    {
        return $this->recStats;
    }

    public function getLRecords(): array
    {
        return $this->lRecords;
    }

    public function getLostPagesList(): string
    {
        return $this->lostPagesList;
    }
}
