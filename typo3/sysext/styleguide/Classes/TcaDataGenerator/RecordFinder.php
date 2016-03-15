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
 * Class contains helper methods to locate uids or pids of specific records
 * in the system.
 */
class RecordFinder
{
    /**
     * Returns a uid list of existing styleguide demo top level pages.
     * These are pages with pid=0 and tx_styleguide_containsdemo set to 'tx_styleguide'.
     * This can be multiple pages if "create" button was clickd multiple times without "delete" in between.
     *
     * @return array
     */
    public function findUidsOfStyleguideEntryPages(): array
    {
        $database = $this->getDatabase();
        $rows = $database->exec_SELECTgetRows(
            'uid',
            'pages',
            'pid = 0'
            . ' AND tx_styleguide_containsdemo=' . $database->fullQuoteStr('tx_styleguide', 'pages')
            . BackendUtility::deleteClause('pages')
        );
        $uids = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $uids[] = (int)$row['uid'];
            }
        }
        return $uids;
    }

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
     * Find uids of styleguide demo be_groups
     *
     * @return array List of uids
     */
    public function findUidsOfDemoBeGroups(): array
    {
        $database = $this->getDatabase();
        $rows = $database->exec_SELECTgetRows(
            'uid',
            'be_groups',
            'tx_styleguide_isdemorecord = 1'
                . BackendUtility::deleteClause('be_groups')
        );
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * Find uids of styleguide demo be_users
     *
     * @return array List of uids
     */
    public function findUidsOfDemoBeUsers(): array
    {
        $database = $this->getDatabase();
        $rows = $database->exec_SELECTgetRows(
            'uid',
            'be_users',
            'tx_styleguide_isdemorecord = 1'
                . BackendUtility::deleteClause('be_users')
        );
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * Find uids of styleguide static data records
     *
     * @return array List of uids
     */
    public function findUidsOfStaticdata(): array
    {
        $database = $this->getDatabase();
        $pageUid = $this->findPidOfMainTableRecord('tx_styleguide_staticdata');
        $rows = $database->exec_SELECTgetRows(
            'uid',
            'tx_styleguide_staticdata',
            'pid = ' . $pageUid
                . BackendUtility::deleteClause('tx_styleguide_staticdata')
        );
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

}