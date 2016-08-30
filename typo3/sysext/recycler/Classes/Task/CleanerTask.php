<?php
namespace TYPO3\CMS\Recycler\Task;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A task that should be run regularly that deletes deleted
 * datasets from the DB.
 */
class CleanerTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * @var int The time period, after which the rows are deleted
     */
    protected $period = 0;

    /**
     * @var array The tables to clean
     */
    protected $tcaTables = [];

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * The main method of the task. Iterates through
     * the tables and calls the cleaning function
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        $success = true;
        $tables = $this->getTcaTables();
        foreach ($tables as $table) {
            if (!$this->cleanTable($table)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Executes the delete-query for the given table
     *
     * @param string $tableName
     * @return bool
     */
    protected function cleanTable($tableName)
    {
        $queryParts = [];
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            $queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] . ' = 1';
            if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp']) {
                $dateBefore = $this->getPeriodAsTimestamp();
                $queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] . ' < ' . $dateBefore;
            }
            $where = implode(' AND ', $queryParts);

            $this->checkFileResourceFieldsBeforeDeletion($tableName, $where);

            $this->getDatabaseConnection()->exec_DELETEquery($tableName, $where);
        }

        return $this->getDatabaseConnection()->sql_error() === '';
    }

    /**
     * Returns the information shown in the task-list
     *
     * @return string Information-text fot the scheduler task-list
     */
    public function getAdditionalInformation()
    {
        $message = '';

        $message .= sprintf(
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescriptionTables'),
            implode(', ', $this->getTcaTables())
        );

        $message .= '; ';

        $message .= sprintf(
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescriptionDays'),
            $this->getPeriod()
        );

        return $message;
    }

    /**
     * Sets the period after which a row is deleted
     *
     * @param int $period
     */
    public function setPeriod($period)
    {
        $this->period = (int)$period;
    }

    /**
     * Returns the period after which a row is deleted
     *
     * @return int
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @return int
     */
    public function getPeriodAsTimestamp()
    {
        return strtotime('-' . $this->getPeriod() . ' days');
    }

    /**
     * Sets the TCA-tables which are cleaned
     *
     * @param array $tcaTables
     */
    public function setTcaTables($tcaTables = [])
    {
        $this->tcaTables = $tcaTables;
    }

    /**
     * Returns the TCA-tables which are cleaned
     *
     * @return array
     */
    public function getTcaTables()
    {
        return $this->tcaTables;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    public function setDatabaseConnection($databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * Checks if the table has fields for uploaded files and removes those files.
     *
     * @param string $table
     * @param string $where
     * @return void
     */
    protected function checkFileResourceFieldsBeforeDeletion($table, $where)
    {
        $fieldList = $this->getFileResourceFields($table);
        if (!empty($fieldList)) {
            $this->deleteFilesForTable($table, $where, $fieldList);
        }
    }

    /**
     * Removes all files from the given field list in the table.
     *
     * @param string $table
     * @param string $where
     * @param array $fieldList
     * @return void
     */
    protected function deleteFilesForTable($table, $where, array $fieldList)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            implode(',', $fieldList),
            $table,
            $where
        );
        foreach ($rows as $row) {
            foreach ($fieldList as $fieldName) {
                $uploadDir = PATH_site . $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['uploadfolder'] . '/';
                $fileList = GeneralUtility::trimExplode(',', $row[$fieldName]);
                foreach ($fileList as $fileName) {
                    @unlink($uploadDir . $fileName);
                }
            }
        }
    }

    /**
     * Checks the $TCA for fields that can list file resources.
     *
     * @param string $table
     * @return array
     */
    protected function getFileResourceFields($table)
    {
        $result = [];
        if (isset($GLOBALS['TCA'][$table]['columns'])) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $fieldConfiguration) {
                if ($fieldConfiguration['config']['type'] === 'group'
                    && $fieldConfiguration['config']['internal_type'] === 'file'
                ) {
                    $result[] = $fieldName;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        if ($this->databaseConnection === null) {
            $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        }
        return $this->databaseConnection;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
