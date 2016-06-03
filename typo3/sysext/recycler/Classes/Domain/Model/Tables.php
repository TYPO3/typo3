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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $tables = array();
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach (RecyclerUtility::getModifyableTables() as $tableName) {
            $deletedField = RecyclerUtility::getDeletedField($tableName);
            if ($deletedField) {
                // Determine whether the table has deleted records:
                $queryBuilder = $connection->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()->removeAll();

                $deletedCount = $queryBuilder->count('uid')
                    ->from($tableName)
                    ->where($queryBuilder->expr()->neq($deletedField, 0))
                    ->execute()
                    ->fetchColumn();

                if ($deletedCount) {
                    /* @var $deletedDataObject DeletedRecords */
                    $deletedDataObject = GeneralUtility::makeInstance(DeletedRecords::class);
                    $deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
                    if (isset($deletedData[$tableName])) {
                        if ($deletedRecordsInTable = count($deletedData[$tableName])) {
                            $deletedRecordsTotal += $deletedRecordsInTable;
                            $tables[] = array(
                                $tableName,
                                $deletedRecordsInTable,
                                $lang->sL($GLOBALS['TCA'][$tableName]['ctrl']['title'])
                            );
                        }
                    }
                }
            }
        }
        $jsonArray = $tables;
        array_unshift($jsonArray, array(
            '',
            $deletedRecordsTotal,
            $lang->sL('LLL:EXT:recycler/mod1/locallang.xlf:label_allrecordtypes')
        ));
        return $jsonArray;
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
