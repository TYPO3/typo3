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

use TYPO3\CMS\Backend\History\RecordHistory;
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

    public function getCreateUserIds(): array
    {
        $createUserIds = [];
        $recordHistory = GeneralUtility::makeInstance(RecordHistory::class);

        foreach ($this->getIdsPerTable() as $tableName => $ids) {
            $historyRecords = $recordHistory->getCreationInformationForMultipleRecords($tableName, $ids);
            foreach ($historyRecords as $historyRecord) {
                if ($historyRecord['actiontype'] === 'BE') {
                    $createUserIds[] = (int)$historyRecord['userid'];
                }
            }
        }
        return array_unique($createUserIds);
    }
}
