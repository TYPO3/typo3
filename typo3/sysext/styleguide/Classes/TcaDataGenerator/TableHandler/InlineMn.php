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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * Generate data for table tx_styleguide_inline_mn
 */
class InlineMn extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * @var string Table name to match
     */
    protected $tableName = 'tx_styleguide_inline_mn';

    /**
     * Create 1 main row, 4 child child rows, add 2 child child rows in mn
     *
     * @param string $tableName
     * @return string
     */
    public function handle(string $tableName)
    {
        $database = $this->getDatabase();

        /** @var RecordFinder $recordFinder */
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $pidOfMainTable = $recordFinder->findPidOfMainTableRecord($tableName);
        /** @var RecordData $recordData */
        $recordData = GeneralUtility::makeInstance(RecordData::class);

        $childRelationUids = [];
        $numberOfChildRelationsToCreate = 2;
        $numberOfChildRows = 4;
        for ($i = 0; $i < $numberOfChildRows; $i ++) {
            $fieldValues = [
                'pid' => $pidOfMainTable,
            ];
            $database->exec_INSERTquery('tx_styleguide_inline_mn_child', $fieldValues);
            $fieldValues['uid'] = $database->sql_insert_id();
            if (count($childRelationUids) < $numberOfChildRelationsToCreate) {
                $childRelationUids[] = $fieldValues['uid'];
            }
            $fieldValues = $recordData->generate('tx_styleguide_inline_mn_child', $fieldValues);
            $database->exec_UPDATEquery(
                'tx_styleguide_inline_mn_child',
                'uid = ' . $fieldValues['uid'],
                $fieldValues
            );
        }

        $fieldValues = [
            'pid' => $pidOfMainTable,
            'inline_1' => $numberOfChildRelationsToCreate,
        ];
        $database->exec_INSERTquery($tableName, $fieldValues);
        $parentid = $fieldValues['uid'] = $database->sql_insert_id();
        $fieldValues = $recordData->generate($tableName, $fieldValues);
        $database->exec_UPDATEquery(
            $tableName,
            'uid = ' . $fieldValues['uid'],
            $fieldValues
        );

        foreach ($childRelationUids as $uid) {
            $mmFieldValues = [
                'pid' => $pidOfMainTable,
                'parentid' => $parentid,
                'childid' => $uid,
            ];
            $database->exec_INSERTquery(
                'tx_styleguide_inline_mn_mm',
                $mmFieldValues
            );
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
