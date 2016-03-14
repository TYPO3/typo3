<?php
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * styleguide pages use field tx_styleguide_containsdemo to specify a page contains
 * only styleguide records and which tables are stored on the pages.
 *
 * This helper class finds the appropriate page uid for a specific table name.
 */
class PageFinder
{
    /**
     * "Main" tables have a single page they are located on with their possible children.
     * The methods find this page by getting the highest uid of a page where field
     * tx_styleguide_containsdemo is set to given table name.
     *
     * @param string $tableName
     * @return int
     * @throws Exception
     */
    public function findPidOfMainTableRecord(string $tableName): int
    {
        $database = $this->getDatabase();
        $row = $database->exec_SELECTgetSingleRow(
            'uid',
            'pages',
            'tx_styleguide_containsdemo=' . $database->fullQuoteStr($tableName, 'pages')
                . BackendUtility::deleteClause('pages'),
            '',
            'pid DESC'
        );
        if (!count($row) === 1) {
            throw new Exception(
                'Found no page for main table ' . $tableName,
                1457690656
            );
        }
        return (int)$row['uid'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

}