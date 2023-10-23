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

namespace TYPO3\CMS\Scheduler\Domain\Repository;

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

/**
 * Repository class to fetch tasks available in the systems ready to be executed
 */
class SchedulerTaskRepository
{
    protected const TABLE_NAME = 'tx_scheduler_task';

    public function __construct(
        protected readonly TaskSerializer $taskSerializer,
    ) {}

    /**
     * Adds a task to the pool
     *
     * @param AbstractTask $task The object representing the task to add
     * @return bool TRUE if the task was successfully added, FALSE otherwise
     */
    public function add(AbstractTask $task): bool
    {
        $taskUid = $task->getTaskUid();
        if (!empty($taskUid)) {
            return false;
        }
        $fields = [
            'crdate' => $GLOBALS['EXEC_TIME'],
            'disable' => (int)$task->isDisabled(),
            'description' => $task->getDescription(),
            'task_group' => $task->getTaskGroup(),
            'serialized_task_object' => 'RESERVED',
        ];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $result = $connection->insert(
            self::TABLE_NAME,
            $fields,
            ['serialized_task_object' => Connection::PARAM_LOB]
        );
        if ($result) {
            $task->setTaskUid((int)$connection->lastInsertId(self::TABLE_NAME));
            $this->update($task);
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Removes a task completely from the system.
     *
     * @param int|AbstractTask $task The object representing the task to delete
     * @return bool TRUE if task was successfully deleted, FALSE otherwise
     */
    public function remove(int|AbstractTask $task): bool
    {
        $taskUid = is_int($task) ? $task : $task->getTaskUid();
        if (empty($taskUid)) {
            return false;
        }
        $affectedRows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->update(self::TABLE_NAME, ['deleted' => 1], ['uid' => $taskUid]);
        return $affectedRows === 1;
    }

    /**
     * Update a task in the pool.
     */
    public function update(AbstractTask $task): bool
    {
        $result = true;
        $taskUid = $task->getTaskUid();
        if (empty($taskUid)) {
            return false;
        }
        try {
            if ($task->getRunOnNextCronJob()) {
                $executionTime = time();
            } else {
                $executionTime = $task->getNextDueExecution();
            }
            $task->setExecutionTime($executionTime);
        } catch (\Exception $e) {
            $task->setDisabled(true);
            $executionTime = 0;
        }
        $task->unsetScheduler();
        $fields = [
            'nextexecution' => $executionTime,
            'disable' => (int)$task->isDisabled(),
            'description' => $task->getDescription(),
            'task_group' => $task->getTaskGroup(),
            'serialized_task_object' => serialize($task),
        ];
        try {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE_NAME)
                ->update(
                    self::TABLE_NAME,
                    $fields,
                    ['uid' => $taskUid],
                    ['serialized_task_object' => Connection::PARAM_LOB]
                );
        } catch (DBALException $e) {
            $result = false;
        }
        return $result;
    }

    /**
     * This method is used to get the database record for a given task
     * It returns the database record and not the task object
     *
     * @param int $uid Primary key of the task to get
     * @return array|null Database record for the task
     * @see findByUid()
     */
    public function findRecordByUid(int $uid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        if (empty($row)) {
            return null;
        }
        return $row;
    }

    /**
     * Fetches and unserializes a task object from the db with the given $uid. The object representing
     * the next due task is returned.
     * If there are no due tasks the method throws an exception.
     *
     * @param int $uid Primary key of a task
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function findByUid(int $uid): AbstractTask
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder->select('uid', 'serialized_task_object')
            ->from(self::TABLE_NAME)
            ->setMaxResults(1)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        if (empty($row)) {
            // Although an uid was passed, no task with given was found
            throw new \OutOfBoundsException('No task with id ' . $uid . ' found', 1422044826);
        }

        return $this->createValidTaskObjectOrDisableTask($row);
    }

    /**
     * Fetch and unserialize a task object from the db. Returns the object representing the
     * next due task is returned. If there are no due tasks the method throws an exception.
     *
     * @return AbstractTask The fetched task object
     * @throws \UnexpectedValueException
     */
    public function findNextExecutableTask(): ?AbstractTask
    {
        // If no uid is given, take any non-disabled task which has a next execution time in the past
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder->select('t.uid', 't.serialized_task_object')
            ->from(self::TABLE_NAME, 't')
            ->setMaxResults(1);
        // Define where clause
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->leftJoin(
            't',
            'tx_scheduler_task_group',
            'g',
            $queryBuilder->expr()->eq('t.task_group', $queryBuilder->quoteIdentifier('g.uid'))
        );
        $queryBuilder->where(
            $queryBuilder->expr()->eq('t.disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $queryBuilder->expr()->neq(
                't.nextexecution',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->lte(
                't.nextexecution',
                $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('g.hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->isNull('g.hidden')
            ),
            $queryBuilder->expr()->eq('t.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
        );
        $queryBuilder->orderBy('t.nextexecution', 'ASC');

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        if (empty($row)) {
            return null;
        }

        return $this->createValidTaskObjectOrDisableTask($row);
    }

    /**
     * @internal This will get split up into errored classes
     */
    public function getGroupedTasks(): array
    {
        $registeredClasses = GeneralUtility::makeInstance(TaskService::class)->getAvailableTaskTypes();

        // Get all registered tasks
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder->select('t.*')
            ->addSelect(
                'g.groupName AS taskGroupName',
                'g.description AS taskGroupDescription',
                'g.uid AS taskGroupId',
                'g.deleted AS isTaskGroupDeleted',
                'g.hidden AS isTaskGroupHidden',
            )
            ->from(self::TABLE_NAME, 't')
            ->leftJoin(
                't',
                'tx_scheduler_task_group',
                'g',
                $queryBuilder->expr()->eq('t.task_group', $queryBuilder->quoteIdentifier('g.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('t.deleted', 0)
            )
            ->orderBy('g.sorting')
            ->executeQuery();

        $taskGroupsWithTasks = [];
        $errorClasses = [];
        while ($row = $result->fetchAssociative()) {
            $taskData = [
                'uid' => (int)$row['uid'],
                'lastExecutionTime' => (int)$row['lastexecution_time'],
                'lastExecutionContext' => $row['lastexecution_context'],
                'errorMessage' => '',
                'description' => $row['description'],
            ];

            try {
                $taskObject = $this->taskSerializer->deserialize($row['serialized_task_object']);
            } catch (InvalidTaskException $e) {
                $taskData['errorMessage'] = $e->getMessage();
                $taskData['class'] = $this->taskSerializer->extractClassName($row['serialized_task_object']);
                $errorClasses[] = $taskData;
                continue;
            }

            $taskClass = $this->taskSerializer->resolveClassName($taskObject);
            $taskData['class'] = $taskClass;

            if (!$this->isValidTaskObject($taskObject)) {
                $taskData['errorMessage'] = 'The class ' . $taskClass . ' is not a valid task';
                $errorClasses[] = $taskData;
                continue;
            }

            if (!isset($registeredClasses[$taskClass])) {
                $taskData['errorMessage'] = 'The class ' . $taskClass . ' is not a registered task';
                $errorClasses[] = $taskData;
                continue;
            }

            if ($taskObject instanceof ProgressProviderInterface) {
                $taskData['progress'] = round((float)$taskObject->getProgress(), 2);
            }
            $taskData['classTitle'] = $registeredClasses[$taskClass]['title'];
            $taskData['classExtension'] = $registeredClasses[$taskClass]['extension'];
            $taskData['additionalInformation'] = $taskObject->getAdditionalInformation();
            $taskData['disabled'] = (bool)$row['disable'];
            $taskData['isRunning'] = !empty($row['serialized_executions']);
            $taskData['nextExecution'] = (int)$row['nextexecution'];
            $taskData['type'] = 'single';
            $taskData['frequency'] = '';
            if ($taskObject->getType() === AbstractTask::TYPE_RECURRING) {
                $taskData['type'] = 'recurring';
                $taskData['frequency'] = $taskObject->getExecution()->getCronCmd() ?: $taskObject->getExecution()->getInterval();
            }
            $taskData['multiple'] = (bool)$taskObject->getExecution()->getMultiple();
            $taskData['lastExecutionFailure'] = false;
            if (!empty($row['lastexecution_failure'])) {
                $taskData['lastExecutionFailure'] = true;
                $exceptionArray = @unserialize($row['lastexecution_failure']);
                $taskData['lastExecutionFailureCode'] = '';
                $taskData['lastExecutionFailureMessage'] = '';
                if (is_array($exceptionArray)) {
                    $taskData['lastExecutionFailureCode'] = $exceptionArray['code'];
                    $taskData['lastExecutionFailureMessage'] = $exceptionArray['message'];
                }
            }

            // If a group is deleted or no group is set it needs to go into "not assigned groups"
            $groupIndex = $row['isTaskGroupDeleted'] === 1 || $row['isTaskGroupDeleted'] === null ? 0 : (int)$row['task_group'];
            if (!isset($taskGroupsWithTasks[$groupIndex])) {
                $taskGroupsWithTasks[$groupIndex] = [
                    'uid' => $row['taskGroupId'],
                    'groupName' => $row['taskGroupName'],
                    'description' => $row['taskGroupDescription'],
                    'hidden' => $row['isTaskGroupHidden'],
                    'tasks' => [],
                ];
            }
            $taskGroupsWithTasks[$groupIndex]['tasks'][] = $taskData;
        }

        return [
            'taskGroupsWithTasks' => $taskGroupsWithTasks,
            'errorClasses' => $errorClasses,
        ];
    }

    protected function createValidTaskObjectOrDisableTask(array $row): AbstractTask
    {
        $isInvalidTask = false;
        $task = null;
        try {
            $task = $this->taskSerializer->deserialize($row['serialized_task_object']);
        } catch (InvalidTaskException) {
            $isInvalidTask = true;
        }
        if ($isInvalidTask || !$this->isValidTaskObject($task)) {
            // Forcibly set the disabled flag to 1 in the database,
            // so that the task does not come up again and again for execution
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $connectionPool->getConnectionForTable(self::TABLE_NAME)->update(
                self::TABLE_NAME,
                ['disable' => 1],
                ['uid' => (int)$row['uid']]
            );
            // Throw an exception to raise the problem
            // @todo: This should most likely be changed to a specific exception.
            throw new \UnexpectedValueException('Could not unserialize task', 1255083671);
        }

        // The task is valid, return it
        $task->setScheduler();
        if ($task->getTaskGroup() === null) {
            // Fix invalid task_group=NULL settings in order to avoid exceptions when saving on PostgreSQL
            $task->setTaskGroup(0);
        }
        return $task;
    }

    /**
     * Fetch and unserialize task objects selected with some (SQL) condition
     */
    public function findNextExecutableTaskForUid(int $uid): ?AbstractTask
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder
            ->select('serialized_task_object')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('nextexecution', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte('nextexecution', $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)),
            );

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            try {
                $task = $this->taskSerializer->deserialize($row['serialized_task_object']);
            } catch (InvalidTaskException) {
                continue;
            }

            // Add the task to the list only if it is valid
            if ($this->isValidTaskObject($task)) {
                $task->setScheduler();
                return $task;
            }
        }
        return null;
    }

    public function isTaskMarkedAsRunning(AbstractTask $task): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('serialized_executions')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($task->getTaskUid(), Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($row && !empty($row['serialized_executions'])) {
            return true;
        }
        return false;
    }

    /**
     * This method adds current execution to the execution list.
     * It also logs the execution time and mode
     *
     * @return int Execution id
     */
    public function addExecutionToTask(AbstractTask $task): int
    {
        $row = $this->findRecordByUid($task->getTaskUid());

        $runningExecutions = [];
        if ($row && !empty($row['serialized_executions'])) {
            $runningExecutions = unserialize($row['serialized_executions']);
        }
        // Count the number of existing executions and use that number as a key
        // (we need to know that number, because it is returned at the end of the method)
        $numExecutions = count($runningExecutions);
        $runningExecutions[$numExecutions] = time();
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->update(
                self::TABLE_NAME,
                [
                    'serialized_executions' => serialize($runningExecutions),
                    'lastexecution_time' => time(),
                    // Define the context in which the script is running
                    'lastexecution_context' => Environment::isCli() ? 'CLI' : 'BE',
                ],
                [
                    'uid' => $task->getTaskUid(),
                ],
                [
                    'serialized_executions' => Connection::PARAM_LOB,
                ]
            );
        return $numExecutions;
    }

    /**
     * Removes given execution from list
     *
     * @param int $executionID Id of the execution to remove.
     * @param string|array|null $failureReason Details of an exception to signal a failed execution
     */
    public function removeExecutionOfTask(AbstractTask $task, int $executionID, array|string $failureReason = null): void
    {
        $row = $this->findRecordByUid($task->getTaskUid());
        if ($row && $row['serialized_executions'] !== '') {
            $runningExecutions = unserialize($row['serialized_executions']);
            // Remove the selected execution
            unset($runningExecutions[$executionID]);
            if (!empty($runningExecutions)) {
                // Re-serialize the updated executions list (if necessary)
                $runningExecutionsSerialized = serialize($runningExecutions);
            } else {
                $runningExecutionsSerialized = '';
            }
            if (is_array($failureReason)) {
                $failureReason = json_encode($failureReason);
            }
            // Save the updated executions list
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE_NAME)
                ->update(
                    self::TABLE_NAME,
                    [
                        'serialized_executions' => $runningExecutionsSerialized,
                        'lastexecution_failure' => (string)$failureReason,
                    ],
                    [
                        'uid' => $task->getTaskUid(),
                    ],
                    [
                        'serialized_executions' => Connection::PARAM_LOB,
                    ]
                );
        }
    }

    /**
     * Clears all marked executions
     *
     * @return bool TRUE if the clearing succeeded, FALSE otherwise
     */
    public function removeAllRegisteredExecutionsForTask(AbstractTask $task): bool
    {
        // Set the serialized executions field to empty
        $result = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->update(
                self::TABLE_NAME,
                ['serialized_executions' => ''],
                ['uid' => $task->getTaskUid()],
                ['serialized_executions' => Connection::PARAM_LOB]
            );
        return (bool)$result;
    }

    /**
     * See if there are any tasks configured at all.
     */
    public function hasTasks(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('deleted', 0)
            );
        return $queryBuilder->executeQuery()->fetchOne() > 0;
    }

    protected function isValidTaskObject($task): bool
    {
        return (new TaskValidator())->isValid($task);
    }
}
