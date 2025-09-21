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

namespace TYPO3\CMS\Scheduler\Migration;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;

/**
 * @since 14.0
 * @internal This class is only meant to be used within EXT:scheduler and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('schedulerDatabaseStorageMigration')]
class SchedulerDatabaseStorageMigration implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'tx_scheduler_task';

    public function getTitle(): string
    {
        return 'Migrate the contents of the tx_scheduler_task database table into a more structured form.';
    }

    public function getDescription(): string
    {
        return 'Each Scheduler Task was previously stored in a serialized object format in the database. This update wizard migrates records of this type to a JSON-formatted storage in the database. If this wizard does not disappear, it means there are tasks that failed to be migrated and may need manual inspection or re-creation. When this happens, inspect all tasks of tx_scheduler_task where the "tasktype" column is empty.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);
        $taskSerializer = GeneralUtility::makeInstance(TaskSerializer::class);
        $hasFailures = false;
        foreach ($this->getRecordsToUpdate() as $record) {
            try {
                // unserialize() will only give a E_NOTICE and false result, not throw an error. Silence this
                // (for tests) and operate on the "false". If future PHP promotes this to an exception, the Throwable
                // catch will kick in.
                $taskObject = @unserialize($record['serialized_task_object']);
                if ($taskObject instanceof AbstractTask) {
                    $connection->update(
                        self::TABLE_NAME,
                        [
                            'tasktype' => $taskObject->getTaskType(),
                            'parameters' => $taskObject->getTaskParameters(),
                            'execution_details' => $taskObject->getExecution()?->toArray(),
                        ],
                        ['uid' => (int)$record['uid']]
                    );
                } elseif ($taskObject instanceof \__PHP_Incomplete_Class) {
                    $objectVars = get_mangled_object_vars($taskObject);
                    $properties = [];
                    $executionDetails = null;
                    $taskType = null;
                    foreach ($objectVars as $key => $value) {
                        $key = trim($key);
                        $key = trim($key, "*\0");
                        $key = trim($key);
                        if ($key === '__PHP_Incomplete_Class_Name') {
                            $taskType = $value;
                        } else {
                            switch ($key) {
                                case '__PHP_Incomplete_Class_Name':
                                    $taskType = $value;
                                    break;
                                case 'execution':
                                    $executionDetails = $value;
                                    break;
                                case 'progress':
                                case 'scheduler':
                                case 'taskUid':
                                case 'disabled':
                                case 'runOnNextCronJob':   // mapped to "task_group" in the database
                                case 'executionTime':   // mapped to "next_execution" in the database
                                case 'taskGroup':   // mapped to "task_group" in the database
                                case 'description':
                                    break;
                                default:
                                    if (is_scalar($value) || is_null($value)) {
                                        $properties[$key] = $value;
                                    }
                            }
                        }
                    }
                    $connection->update(
                        self::TABLE_NAME,
                        [
                            'tasktype' => $taskType,
                            'parameters' => $properties,
                            'execution_details' => $executionDetails?->toArray(),
                        ],
                        ['uid' => (int)$record['uid']]
                    );
                } else {
                    // This happens if unserialize() failed (gracefully).
                    // Wizard shall not be marked as completed and show up again to let people know.
                    $hasFailures = true;
                }
            } catch (\Throwable) {
                $className = $taskSerializer->extractClassName($record['serialized_task_object']);
                if ($className) {
                    $connection->update(
                        self::TABLE_NAME,
                        [
                            'tasktype' => $className,
                        ],
                        ['uid' => (int)$record['uid']]
                    );
                } else {
                    // We have a problem here if $className is empty, we don't change something here,
                    // so the upgrade wizard will show up again, and people know there is a problem.
                    $hasFailures = true;
                }
            }
        }

        return !$hasFailures;
    }

    protected function hasRecordsToUpdate(): bool
    {
        // Check if table exists
        if (!$this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)->createSchemaManager()->tableExists(self::TABLE_NAME)) {
            return false;
        }
        return (bool)$this->getPreparedQueryBuilder()->count('uid')->executeQuery()->fetchOne();
    }

    protected function getRecordsToUpdate(): array
    {
        return $this->getPreparedQueryBuilder()->select('*')->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        // This is done by intention, so the upgrade wizard continues to work even if we introduce TCA for tx_scheduler_task
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq(
                        'tasktype',
                        $queryBuilder->createNamedParameter('')
                    ),
                    $queryBuilder->expr()->isNull('tasktype')
                )
            );

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
