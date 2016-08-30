<?php
namespace TYPO3\CMS\Recycler\Domain\Model;

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

use TYPO3\CMS\Recycler\Utility\RecyclerUtility;

/**
 * Model class for the 'recycler' extension.
 */
class Tables
{
    /**
     * Get tables for menu example
     *
     * @param int $startUid UID from selected page
     * @param int $depth How many levels recursive
     * @return string The tables to be displayed
     */
    public function getTables($startUid, $depth = 0)
    {
        $deletedRecordsTotal = 0;
        $lang = $this->getLanguageService();
        $tables = [];
        foreach (RecyclerUtility::getModifyableTables() as $tableName) {
            $deletedField = RecyclerUtility::getDeletedField($tableName);
            if ($deletedField) {
                // Determine whether the table has deleted records:
                $deletedCount = $this->getDatabaseConnection()->exec_SELECTcountRows('uid', $tableName, $deletedField . '<>0');
                if ($deletedCount) {
                    /* @var $deletedDataObject \TYPO3\CMS\Recycler\Domain\Model\DeletedRecords */
                    $deletedDataObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Recycler\Domain\Model\DeletedRecords::class);
                    $deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
                    if (isset($deletedData[$tableName])) {
                        if ($deletedRecordsInTable = count($deletedData[$tableName])) {
                            $deletedRecordsTotal += $deletedRecordsInTable;
                            $tables[] = [
                                $tableName,
                                $deletedRecordsInTable,
                                RecyclerUtility::getUtf8String($lang->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']))
                            ];
                        }
                    }
                }
            }
        }
        $jsonArray = $tables;
        array_unshift($jsonArray, [
            '',
            $deletedRecordsTotal,
            $lang->sL('LLL:EXT:recycler/mod1/locallang.xlf:label_allrecordtypes')
        ]);
        return $jsonArray;
    }

    /**
     * Returns an instance of DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
