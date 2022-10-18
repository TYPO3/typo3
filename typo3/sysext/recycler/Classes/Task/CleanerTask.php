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

namespace TYPO3\CMS\Recycler\Task;

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that deletes deleted
 * datasets from the DB.
 * @internal This class is a specific scheduler task implementation and is not part of the Public TYPO3 API.
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
    protected $tcaTables = [];

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
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$tableName]['ctrl']['delete'],
                    $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                )
                ,
            ];

            if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] ?? null) {
                $dateBefore = $this->getPeriodAsTimestamp();
                $constraints[] = $queryBuilder->expr()->lt(
                    $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'],
                    $queryBuilder->createNamedParameter($dateBefore, Connection::PARAM_INT)
                );
            }
            try {
                $queryBuilder->delete($tableName)
                    ->where(...$constraints)
                    ->executeStatement();
            } catch (DBALException $e) {
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
        $timeStamp = strtotime('-' . $this->getPeriod() . ' days');
        if ($timeStamp === false) {
            throw new \InvalidArgumentException('Period must be an integer.', 1623097600);
        }
        return $timeStamp;
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
}
