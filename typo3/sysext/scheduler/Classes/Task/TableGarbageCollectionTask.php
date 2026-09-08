<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Scheduler\Task;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Remove old entries from tables.
 *
 * This task deletes rows from tables older than the given number of days.
 *
 * Available tables must be registered in
 * $GLOBALS['TCA']['tx_scheduler_task']['types'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['taskOptions']['tables']
 *
 * See scheduler_table_garbage_collection_task.php of scheduler extension for an example.
 *
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class TableGarbageCollectionTask extends AbstractTask
{
    /**
     * Maximum number of rows a single delete statement is allowed to remove.
     *
     * Removing all expired rows of a table with one statement results in a single
     * huge transaction, which does not scale with the size of the table: it keeps
     * locks for a long time and can exceed transaction size limits, for instance
     * the ones of a Galera cluster.
     */
    protected const DELETE_BATCH_SIZE = 10000;

    /**
     * @var bool True if all tables should be cleaned up
     */
    public $allTables = false;

    /**
     * @var int Number of days
     */
    public $numberOfDays = 0;

    /**
     * @var string Table to clean up
     */
    public $table = '';

    /**
     * Execute garbage collection, called by scheduler.
     *
     * @throws \RuntimeException If configured table was not cleaned up
     * @return bool TRUE if task run was successful
     */
    public function execute()
    {
        $tableConfigurations = $this->getTableConfiguration();
        $tableHandled = false;
        foreach ($tableConfigurations as $tableName => $configuration) {
            if ($this->allTables || $tableName === $this->table) {
                $this->handleTable($tableName, $configuration);
                $tableHandled = true;
            }
        }
        if (!$tableHandled) {
            throw new \RuntimeException(self::class . ' misconfiguration: ' . $this->table . ' does not exist in configuration', 1308354399);
        }
        return true;
    }

    /**
     * Execute clean up of a specific table
     *
     * @throws \RuntimeException If table configuration is broken
     * @param string $table The table to handle
     * @param array $configuration Clean up configuration
     * @return bool TRUE if cleanup was successful
     */
    protected function handleTable(string $table, array $configuration): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        if (!empty($configuration['expireField'])) {
            $expirationField = (string)$configuration['expireField'];
            $expirationTimestamp = (int)$GLOBALS['EXEC_TIME'];
            $isExpireField = true;
        } elseif (!empty($configuration['dateField'])) {
            $expirationField = (string)$configuration['dateField'];
            $isExpireField = false;
            if ($this->allTables) {
                if (!isset($configuration['expirePeriod'])) {
                    throw new \RuntimeException(self::class . ' misconfiguration: No expirePeriod defined for table ' . $table, 1308355095);
                }
                $numberOfDays = (int)$configuration['expirePeriod'];
            } else {
                $numberOfDays = $this->numberOfDays;
                if (isset($configuration['expirePeriod']) && $numberOfDays <= 0) {
                    $numberOfDays = (int)$configuration['expirePeriod'];
                }
            }
            $expirationTimestamp = (int)strtotime('-' . $numberOfDays . 'days');
        } else {
            throw new \RuntimeException(self::class . ' misconfiguration: Either expireField or dateField must be defined for table ' . $table, 1308355268);
        }

        try {
            $this->deleteExpiredRows($connection, $table, $expirationField, $expirationTimestamp, $isExpireField);
        } catch (DBALException $e) {
            throw new \RuntimeException(self::class . ' failed for table ' . $table . ' with error: ' . $e->getMessage(), 1308255491);
        }
        return true;
    }

    /**
     * Removes the expired rows of a table, limiting the number of rows removed by a
     * single delete statement to self::DELETE_BATCH_SIZE.
     *
     * The number of rounds is derived from the number of expired rows, with one
     * additional round for rows expiring while the task is running. Rows left over
     * after that are removed by the next run of this task.
     *
     * The statements are not wrapped into a transaction on purpose: every batch is
     * committed on its own, so concurrent operations on the table are not blocked
     * for the whole runtime of the task.
     */
    protected function deleteExpiredRows(
        Connection $connection,
        string $table,
        string $expirationField,
        int $expirationTimestamp,
        bool $isExpireField
    ): void {
        $countQueryBuilder = $connection->createQueryBuilder();
        $countQueryBuilder->getRestrictions()->removeAll();
        $countQueryBuilder->count('*')->from($table);
        $this->addExpirationRestriction($countQueryBuilder, $expirationField, $expirationTimestamp, $isExpireField);
        $expiredRows = (int)$countQueryBuilder->executeQuery()->fetchOne();
        if ($expiredRows === 0) {
            return;
        }

        $batchKey = $expiredRows > static::DELETE_BATCH_SIZE
            ? $this->determineBatchKey($connection, $table)
            : null;
        if ($batchKey === null) {
            // Either there are not enough rows to justify batching, or the table has no
            // single column primary key a batch could be limited with. Remove the rows
            // with one statement, as it has been done for all tables before.
            $this->createDeleteQueryBuilder($connection, $table, $expirationField, $expirationTimestamp, $isExpireField)
                ->executeStatement();
            return;
        }
        [$batchKeyField, $batchKeyParameterType] = $batchKey;

        // Determines the primary key value of the last row of the next batch. This is
        // executed once per round with unchanged values and is prepared only once.
        $boundaryQueryBuilder = $connection->createQueryBuilder();
        $boundaryQueryBuilder->getRestrictions()->removeAll();
        $boundaryQueryBuilder
            ->select($batchKeyField)
            ->from($table)
            ->orderBy($batchKeyField)
            ->setFirstResult(static::DELETE_BATCH_SIZE - 1)
            ->setMaxResults(1);
        $this->addExpirationRestriction($boundaryQueryBuilder, $expirationField, $expirationTimestamp, $isExpireField);
        $boundaryStatement = $boundaryQueryBuilder->prepare();

        // Removes the expired rows up to that boundary. Ordering the boundary query by
        // the primary key makes them the oldest expired rows and limits this statement
        // to self::DELETE_BATCH_SIZE rows. Prepared once and re-executed per round.
        $batchDeleteQueryBuilder = $this->createDeleteQueryBuilder($connection, $table, $expirationField, $expirationTimestamp, $isExpireField);
        $batchDeleteQueryBuilder->andWhere(
            $batchDeleteQueryBuilder->expr()->lte(
                $batchKeyField,
                $batchDeleteQueryBuilder->createPositionalParameter(0, $batchKeyParameterType)
            )
        );
        // The boundary is the last created positional parameter and is bound per round.
        $boundaryParameterPosition = count($batchDeleteQueryBuilder->getParameters());
        $batchDeleteStatement = $batchDeleteQueryBuilder->prepare();

        $rounds = (int)ceil($expiredRows / static::DELETE_BATCH_SIZE) + 1;
        for ($round = 0; $round < $rounds; $round++) {
            $result = $boundaryStatement->executeQuery();
            $boundary = $result->fetchOne();
            $result->free();
            if ($boundary === false) {
                // Less than a full batch is left, remove it with a final statement.
                $this->createDeleteQueryBuilder($connection, $table, $expirationField, $expirationTimestamp, $isExpireField)
                    ->executeStatement();
                return;
            }
            $batchDeleteStatement->bindValue(
                $boundaryParameterPosition,
                $batchKeyParameterType === Connection::PARAM_INT ? (int)$boundary : (string)$boundary,
                $batchKeyParameterType
            );
            $batchDeleteStatement->executeStatement();
        }
    }

    /**
     * The field the batches are limited with and the parameter type of its values.
     * That is the single column primary key of the table, for instance "uid". Returns
     * null if the table has no primary key or one spanning several columns, as those
     * cannot be used as batch boundary.
     *
     * @return array{string, ParameterType}|null
     */
    protected function determineBatchKey(Connection $connection, string $table): ?array
    {
        $tableSchema = $connection->createSchemaManager()->introspectTable($table);
        $primaryKeyConstraint = $tableSchema->getPrimaryKeyConstraint();
        if ($primaryKeyConstraint === null) {
            return null;
        }
        $primaryKeyColumnNames = $primaryKeyConstraint->getColumnNames();
        if (count($primaryKeyColumnNames) !== 1) {
            return null;
        }
        $batchKeyField = $primaryKeyColumnNames[0]->getIdentifier()->getValue();
        return [
            $batchKeyField,
            $tableSchema->getColumn($batchKeyField)->getType() instanceof PhpIntegerMappingType
                ? Connection::PARAM_INT
                : Connection::PARAM_STR,
        ];
    }

    protected function createDeleteQueryBuilder(
        Connection $connection,
        string $table,
        string $expirationField,
        int $expirationTimestamp,
        bool $isExpireField
    ): QueryBuilder {
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->delete($table);
        $this->addExpirationRestriction($queryBuilder, $expirationField, $expirationTimestamp, $isExpireField);
        return $queryBuilder;
    }

    /**
     * Restricts a query to the expired rows of a table. An "expireField" holds the
     * point in time a row expires, where the value 0 means it never expires, while a
     * "dateField" holds the creation time of a row, which expires with the configured
     * period.
     */
    protected function addExpirationRestriction(
        QueryBuilder $queryBuilder,
        string $expirationField,
        int $expirationTimestamp,
        bool $isExpireField
    ): void {
        if ($isExpireField) {
            $queryBuilder->where(
                $queryBuilder->expr()->lte($expirationField, $queryBuilder->createPositionalParameter($expirationTimestamp, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt($expirationField, $queryBuilder->createPositionalParameter(0, Connection::PARAM_INT))
            );
            return;
        }
        $queryBuilder->where(
            $queryBuilder->expr()->lt($expirationField, $queryBuilder->createPositionalParameter($expirationTimestamp, Connection::PARAM_INT))
        );
    }

    /**
     * This method returns the selected table as additional information
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        if ($this->allTables) {
            $message = $this->getLanguageService()?->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.additionalInformationAllTables');
        } else {
            $message = sprintf($this->getLanguageService()?->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.additionalInformationTable'), $this->table);
        }
        return $message;
    }

    public function getTaskParameters(): array
    {
        return [
            'all_tables' => $this->allTables,
            'number_of_days' => $this->numberOfDays,
            'selected_tables' => $this->table,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $this->allTables = (bool)($parameters['allTables'] ?? $parameters['all_tables'] ?? false);
        $this->numberOfDays = (int)($parameters['numberOfDays'] ?? $parameters['number_of_days'] ?? 0);
        $this->table = (string)($parameters['table'] ?? $parameters['selected_tables'] ?? '');
    }

    public function getCleanableTables(array &$config): void
    {
        foreach ($this->getTableConfiguration() as $tableName => $tableConfiguration) {
            $config['items'][] = [
                'label' => $tableName . (($tableConfiguration['expirePeriod'] ?? false) ? ' [expirePeriod: ' . $tableConfiguration['expirePeriod'] . ']' : '') . (($tableConfiguration['dateField'] ?? false) ? ' [dateField: ' . $tableConfiguration['dateField'] . ']' : ''),
                'value' => $tableName,
            ];
        }
    }

    public function getTableConfiguration(): array
    {
        $tableConfiguration = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('tx_scheduler_task.' . self::class)->getRawConfiguration()['taskOptions']['tables'] ?? [];

        $tableConfigurationFromConfVars = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][self::class]['options']['tables'] ?? [];
        if (!empty($tableConfigurationFromConfVars)) {
            // @deprecated will be removed in v16: this SC_OPTIONS fallback is intentionally
            //             kept beyond v15 because SC_OPTIONS-based scheduler task registration
            //             is still read in TaskService::getAvailableTaskTypes() to keep legacy
            //             (non-native) tasks migratable. Remove together with that support.
            trigger_error('Usage of $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'scheduler\'][\'tasks\'][' . self::class . '][\'options\'][\'tables\'] to define table options is deprecated and will stop working in TYPO3 v16. Use $tca[\'tx_scheduler_task\'][\'types\'][' . self::class . '][\'taskOptions\'][\'tables\'] instead.', E_USER_DEPRECATED);
            if (is_array($tableConfigurationFromConfVars)) {
                $tableConfiguration = array_replace_recursive($tableConfiguration, $tableConfigurationFromConfVars);
            }
        }

        return $tableConfiguration;
    }
}
