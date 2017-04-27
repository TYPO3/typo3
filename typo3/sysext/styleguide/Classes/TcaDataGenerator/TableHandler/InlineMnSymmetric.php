<?php
declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * Generate data for table tx_styleguide_inline_mnsymmetric
 */
class InlineMnSymmetric extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * @var string Table name to match
     */
    protected $tableName = 'tx_styleguide_inline_mnsymmetric';

    /**
     * Create 4 rows, add row 2 and 3 as branch to row 1
     *
     * @param string $tableName
     * @return string
     */
    public function handle(string $tableName)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $pidOfMainTable = $recordFinder->findPidOfMainTableRecord($tableName);
        $recordData = GeneralUtility::makeInstance(RecordData::class);

        $isFirst = true;
        $numberOfRelationsForFirstRecord = 2;
        $relationUids = [];
        $uidOfFirstRecord = null;
        for ($i = 0; $i < 4; $i++) {
            $fieldValues = [
                'pid' => $pidOfMainTable,
            ];
            $connection = $connectionPool->getConnectionForTable($tableName);
            $connection->insert($tableName, $fieldValues);
            $fieldValues['uid'] = $connection->lastInsertId($tableName);
            if ($isFirst) {
                $fieldValues['branches'] = $numberOfRelationsForFirstRecord;
                $uidOfFirstRecord = $fieldValues['uid'];
            }
            $fieldValues = $recordData->generate($tableName, $fieldValues);
            $connection->update(
                $tableName,
                $fieldValues,
                [ 'uid' => $fieldValues['uid'] ]
            );

            $this->generateTranslatedRecords($tableName, $fieldValues);

            if (!$isFirst && count($relationUids) < $numberOfRelationsForFirstRecord) {
                $relationUids[] = $fieldValues['uid'];
            }

            $isFirst = false;
        }

        foreach ($relationUids as $uid) {
            $mmFieldValues = [
                'pid' => $pidOfMainTable,
                'hotelid' => $uidOfFirstRecord,
                'branchid' => $uid,
            ];
            $connection = $connectionPool->getConnectionForTable('tx_styleguide_inline_mnsymmetric_mm');
            $connection->insert(
                'tx_styleguide_inline_mnsymmetric_mm',
                $mmFieldValues
            );
        }
    }
}
