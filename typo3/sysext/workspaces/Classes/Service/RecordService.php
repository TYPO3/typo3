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

namespace TYPO3\CMS\Workspaces\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord;

/**
 * Service for records
 */
class RecordService implements SingletonInterface
{
    /**
     * @var DatabaseRecord[]
     */
    protected $records = [];

    /**
     * @param string $tableName
     * @param int $id
     */
    public function add($tableName, $id)
    {
        $databaseRecord = DatabaseRecord::create($tableName, $id);
        if (!isset($this->records[$databaseRecord->getIdentifier()])) {
            $this->records[$databaseRecord->getIdentifier()] = $databaseRecord;
        }
    }

    /**
     * @return array
     */
    public function getIdsPerTable()
    {
        $idsPerTable = [];
        foreach ($this->records as $databaseRecord) {
            if (!isset($idsPerTable[$databaseRecord->getTable()])) {
                $idsPerTable[$databaseRecord->getTable()] = [];
            }
            $idsPerTable[$databaseRecord->getTable()][] = $databaseRecord->getUid();
        }
        return $idsPerTable;
    }

    /**
     * @return array
     */
    public function getCreateUserIds()
    {
        $createUserIds = [];
        foreach ($this->getIdsPerTable() as $tableName => $ids) {
            if (empty($GLOBALS['TCA'][$tableName]['ctrl']['cruser_id'])) {
                continue;
            }
            $createUserIdFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['cruser_id'];

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();

            $records = $queryBuilder
                ->select($createUserIdFieldName)
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->groupBy($createUserIdFieldName)
                ->executeQuery()
                ->fetchAllAssociative();

            $records = array_column($records, $createUserIdFieldName);

            if (!empty($records)) {
                $createUserIds = array_merge($createUserIds, $records);
            }
        }
        return array_unique($createUserIds);
    }
}
