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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that permanently removes soft-deleted
 * datasets from the DB.
 * @internal This class is a specific scheduler task implementation and is not part of the Public TYPO3 API.
 */
class CleanerTask extends AbstractTask
{
    /**
     * @var int The time period, after which the rows are deleted
     */
    protected int $period = 0;

    /**
     * @var array The tables to clean
     */
    protected array $tcaTables = [];

    /**
     * The main method of the task. Iterates through
     * the tables and calls the cleaning function
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        $success = true;
        $tables = $this->tcaTables;
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        foreach ($tables as $table) {
            if (!$schemaFactory->has($table)) {
                $success = false;
                continue;
            }
            $schema = $schemaFactory->get($table);
            if (!$schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                continue;
            }
            if (!$this->cleanTable($schema)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Executes the delete-query for the given table
     */
    protected function cleanTable(TcaSchema $schema): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($schema->getName());
        $queryBuilder->getRestrictions()->removeAll();
        $deleteField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();

        $constraints = [
            $queryBuilder->expr()->eq(
                $deleteField,
                $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
            ),
        ];

        if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $dateBefore = $this->getPeriodAsTimestamp();
            $constraints[] = $queryBuilder->expr()->lt(
                $schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName(),
                $queryBuilder->createNamedParameter($dateBefore, Connection::PARAM_INT)
            );
        }
        try {
            $queryBuilder
                ->delete($schema->getName())
                ->where(...$constraints)
                ->executeStatement();
        } catch (DBALException) {
            return false;
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
        $message = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescriptionTables'),
            implode(', ', $this->tcaTables)
        );

        $message .= '; ';

        $message .= sprintf(
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescriptionDays'),
            $this->period
        );

        return $message;
    }

    public function getPeriodAsTimestamp(): int
    {
        $timeStamp = strtotime('-' . $this->period . ' days');
        if ($timeStamp === false) {
            throw new \InvalidArgumentException('Period must be an integer.', 1623097600);
        }
        return $timeStamp;
    }

    public function getTaskParameters(): array
    {
        return [
            'selected_tables' => implode(',', $this->tcaTables),
            'number_of_days' => $this->period,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $tcaTables = $parameters['RecyclerCleanerTCA'] ?? $parameters['selected_tables'] ?? [];
        if (is_string($tcaTables)) {
            $tcaTables = GeneralUtility::trimExplode(',', $tcaTables, true);
        }
        $this->tcaTables = $tcaTables;
        $this->period = (int)($parameters['RecyclerCleanerPeriod'] ?? $parameters['number_of_days'] ?? 180);
    }

    /**
     * TCA Item Provider
     */
    public function getAllTcaTables(array &$config): void
    {
        $options = [];
        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        foreach ($tcaSchemaFactory->all() as $table => $schema) {
            if (!$schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                continue;
            }
            $tableTitle = $schema->getTitle($this->getLanguageService()->sL(...));
            $config['items'][] = [
                'label' => $tableTitle . ' (' . $table . ')',
                'value' => $table,
            ];
        }
        ksort($options);
    }
}
