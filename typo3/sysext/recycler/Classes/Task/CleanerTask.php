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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that deletes deleted
 * datasets from the DB.
 */
class CleanerTask extends AbstractTask
{
    /**
     * @var int The time period, after which the rows are deleted
     */
    protected $period = 0;

    /**
     * @var array The tables to clean
     */
    protected $tcaTables = array();

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
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();

            $constraints = [
                $queryBuilder->expr()->eq($GLOBALS['TCA'][$tableName]['ctrl']['delete'], 1),
            ];

            if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp']) {
                $dateBefore = $this->getPeriodAsTimestamp();
                $constraints[] = $queryBuilder->expr()->lt($GLOBALS['TCA'][$tableName]['ctrl']['tstamp'], (int)$dateBefore);
            }
            $this->checkFileResourceFieldsBeforeDeletion($tableName, $constraints);
            try {
                $queryBuilder->delete($tableName)
                    ->where(...$constraints)
                    ->execute();
            } catch (\Doctrine\DBAL\DBALException $e) {
                return false;
            }
        }
        return true;
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
    public function setTcaTables($tcaTables = array())
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
     * Checks if the table has fields for uploaded files and removes those files.
     *
     * @param string $table
     * @param array $constraints
     * @return void
     */
    protected function checkFileResourceFieldsBeforeDeletion($table, array $constraints)
    {
        $fieldList = $this->getFileResourceFields($table);
        if (!empty($fieldList)) {
            $this->deleteFilesForTable($table, $constraints, $fieldList);
        }
    }

    /**
     * Removes all files from the given field list in the table.
     *
     * @param string $table
     * @param array $constraints
     * @param array $fieldList
     * @return void
     */
    protected function deleteFilesForTable($table, array $constraints, array $fieldList)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select(...$fieldList)
            ->from($table)
            ->where(...$constraints)
            ->execute();

        while ($row = $result->fetch()) {
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
        $result = array();
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
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
