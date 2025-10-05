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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

/**
 * Repository class to fetch tasks available in the systems ready to be executed
 *
 * @internal not part of public TYPO3 Core API
 */
#[Autoconfigure(public: true)]
readonly class SchedulerTaskRepository
{
    protected const TABLE_NAME = 'tx_scheduler_task';

    public function __construct(
        protected TaskSerializer $taskSerializer,
        protected TaskService $taskService,
        protected TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Removes a task completely from the system.
     *
     * @param int|AbstractTask $task The object representing the task to delete
     * @return bool TRUE if the task was successfully deleted, FALSE otherwise
     */
    public function remove(int|AbstractTask $task): bool
    {
        $taskUid = is_int($task) ? $task : $task->getTaskUid();
        if (empty($taskUid)) {
            return false;
        }
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            self::TABLE_NAME => [
                $taskUid => [
                    'delete' => 1,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();
        return $dataHandler->errorLog === [];
    }

    /**
     * Update a task in the pool.
     */
    public function update(AbstractTask $task, ?array $fields = null): bool
    {
        $taskUid = $task->getTaskUid();
        if (empty($taskUid)) {
            return false;
        }
        $fields = $fields ?? $this->taskService->getFieldsForRecord($task);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            self::TABLE_NAME => [
                $taskUid => $fields,
            ],
        ], []);
        $dataHandler->process_datamap();
        return true;
    }

    /**
     * Update a task in the pool but only the execution information.
     */
    public function updateExecution(AbstractTask $task, bool $forceDisablingTask = false): void
    {
        $taskUid = $task->getTaskUid();
        if (empty($taskUid)) {
            return;
        }
        $fields = $this->taskService->getFieldsForRecord($task);
        $fields = [
            'nextexecution' => $fields['nextexecution'],
            'disable' => $forceDisablingTask ? true : $fields['disable'],
            'execution_details' => $fields['execution_details'],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            self::TABLE_NAME => [
                $taskUid => $fields,
            ],
        ], []);
        $dataHandler->process_datamap();
    }

    /**
     * Fetches a task object from the db with the given $uid. The object representing
     * the next due task is returned.
     * If there are no tasks due, the method throws an exception.
     *
     * @param int $uid Primary key of a task
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function findByUid(int $uid): AbstractTask
    {
        $row = BackendUtility::getRecord(self::TABLE_NAME, $uid);
        if (empty($row)) {
            // Although an uid was passed, no task with given was found
            throw new \OutOfBoundsException('No task with id ' . $uid . ' found', 1422044826);
        }

        return $this->createValidTaskObjectOrDisableTask($row);
    }

    /**
     * Fetch and unserialize a task object from the db. Returns the object representing the
     * next due task is returned. If there are no due tasks, the method throws an exception.
     *
     * @throws \UnexpectedValueException
     */
    public function findNextExecutableTask(): ?AbstractTask
    {
        // If no uid is given, take any non-disabled task that has a next execution time in the past
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->select(
            't.uid',
            't.crdate',
            't.deleted',
            't.description',
            't.nextexecution',
            't.lastexecution_time',
            't.lastexecution_failure',
            't.lastexecution_context',
            't.serialized_task_object',
            't.disable',
            't.tasktype',
            't.parameters',
            't.execution_details',
            't.serialized_executions',
            't.task_group',
        )
            ->from(self::TABLE_NAME, 't')
            ->setMaxResults(1);
        // Define where clause
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->leftJoin(
            't',
            'tx_scheduler_task_group',
            'g',
            $queryBuilder->expr()->eq('g.uid', $queryBuilder->expr()->castInt($queryBuilder->quoteIdentifier('t.task_group')))
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
     * @todo This will get split up into errored classes
     */
    public function getGroupedTasks(): array
    {
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
                $queryBuilder->expr()->eq('g.uid', $queryBuilder->expr()->castInt($queryBuilder->quoteIdentifier('t.task_group')))
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
                $taskObject = $this->taskSerializer->deserialize($row);
            } catch (InvalidTaskException $e) {
                $taskData['errorMessage'] = $e->getMessage();
                $taskData['taskType'] = $row['tasktype'] ?: $this->taskSerializer->extractClassName($row['serialized_task_object']);
                $errorClasses[] = $taskData;
                continue;
            }

            $taskData['taskType'] = $taskObject->getTaskType();

            if (!$this->isValidTaskObject($taskObject)) {
                $taskData['errorMessage'] = 'The task "' . $taskObject->getTaskType() . ' is not a valid task';
                $errorClasses[] = $taskData;
                continue;
            }

            $taskInformation = $this->taskService->getTaskDetailsFromTask($taskObject);
            if ($taskInformation === null) {
                $taskData['errorMessage'] = 'The task ' . $taskObject->getTaskType() . ' is not a registered task';
                $errorClasses[] = $taskData;
                continue;
            }

            if ($taskObject instanceof ProgressProviderInterface) {
                $taskData['progress'] = round((float)$taskObject->getProgress(), 2);
            }
            $taskData['fullTitle'] = $taskInformation['fullTitle'];
            $taskData['additionalInformation'] = $taskObject->getAdditionalInformation();
            $taskData['disabled'] = (bool)$row['disable'];
            $taskData['isRunning'] = !empty($row['serialized_executions']);
            $taskData['nextExecution'] = (int)$row['nextexecution'];
            $taskData['runningType'] = 'single';
            $taskData['frequency'] = '';
            if ($taskObject->getExecution()->isRecurring()) {
                $taskData['runningType'] = 'recurring';
                $taskData['frequency'] = $taskObject->getExecution()->getCronCmd() ?: $taskObject->getExecution()->getInterval();
            }
            $taskData['multiple'] = (bool)$taskObject->getExecution()->isParallelExecutionAllowed();
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
            $task = $this->taskSerializer->deserialize($row);
        } catch (InvalidTaskException) {
            $isInvalidTask = true;
        }
        if ($isInvalidTask || !$this->isValidTaskObject($task)) {
            $fieldName = $this->tcaSchemaFactory
                ->get(self::TABLE_NAME)
                ->getCapability(TcaSchemaCapability::RestrictionDisabledField)
                ->getFieldName();
            if ((bool)$row[$fieldName] !== true) {
                // Forcibly set the disabled flag to 1 in the database (if not already set), so that the
                // task does not come up again and again for execution. Execute a simple update statement
                // to avoid triggering any DH hook again, which would lead to an infinity loop.
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable(self::TABLE_NAME)
                    ->update(self::TABLE_NAME, [$fieldName => 1], ['uid' => (int)$row['uid']]);
            }
            // Throw an exception to raise the problem
            // @todo: This should most likely be changed to a specific exception.
            throw new \UnexpectedValueException('Could not unserialize task', 1255083671);
        }

        // The task is valid, return it
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
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('nextexecution', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte('nextexecution', $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)),
            );

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            try {
                $task = $this->taskSerializer->deserialize($row);
            } catch (InvalidTaskException) {
                continue;
            }

            // Add the task to the list only if it is valid
            if ($this->isValidTaskObject($task)) {
                return $task;
            }
        }
        return null;
    }

    public function isTaskMarkedAsRunning(AbstractTask $task): bool
    {
        $row = BackendUtility::getRecord(self::TABLE_NAME, $task->getTaskUid());
        return !empty($row['serialized_executions'] ?? null);
    }

    /**
     * This method adds current execution to the execution list.
     * It also logs the execution time and mode
     *
     * The execution id is guaranteed to start from zero if the task has no
     * currently running execution at the time of id allocation.
     *
     * @return int Execution id
     */
    public function addExecutionToTask(AbstractTask $task): int
    {
        while (true) {
            $row = BackendUtility::getRecord(self::TABLE_NAME, $task->getTaskUid());

            if ($row === null) {
                throw new \InvalidArgumentException(
                    'Given task must have a persistence record associated with it',
                    1741257045
                );
            }

            $previousExecutions = isset($row['serialized_executions'])
                ? (string)$row['serialized_executions']
                : null;

            $runningExecutions = $previousExecutions !== null
                && $previousExecutions !== ''
                    ? unserialize($previousExecutions)
                    : [];

            // Count the number of existing executions and use that number as a key
            // (we need to know that number, because it is returned at the end of the method)
            $numExecutions = count($runningExecutions);
            $runningExecutions[$numExecutions] = time();
            $updateCount = GeneralUtility::makeInstance(ConnectionPool::class)
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
                        'serialized_executions' => $previousExecutions,
                    ],
                    [
                        'serialized_executions' => Connection::PARAM_LOB,
                    ]
                );

            if ($updateCount === 1) {
                return $numExecutions;
            }
        }
    }

    /**
     * Removes a given execution from the list
     *
     * @param int $executionID Id of the execution to remove.
     * @param string|array|null $failureReason Details of an exception to signal a failed execution.
     */
    public function removeExecutionOfTask(AbstractTask $task, int $executionID, array|string|null $failureReason = null): void
    {
        while ($row = BackendUtility::getRecord(self::TABLE_NAME, $task->getTaskUid())) {
            $previousExecutions = (string)($row['serialized_executions'] ?? '');
            if ($previousExecutions === '') {
                break;
            }

            $runningExecutions = unserialize($previousExecutions);
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
            $fieldUpdates = [
                'serialized_executions' => $runningExecutionsSerialized,
            ];
            if ($failureReason !== null) {
                $fieldUpdates['lastexecution_failure'] = (string)$failureReason;
            }
            $updateCount = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE_NAME)
                ->update(
                    self::TABLE_NAME,
                    $fieldUpdates,
                    [
                        'uid' => $task->getTaskUid(),
                        'serialized_executions' => $previousExecutions,
                    ],
                    [
                        'serialized_executions' => Connection::PARAM_LOB,
                    ]
                );
            if ($updateCount === 1) {
                break;
            }
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
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME);
        return $queryBuilder->executeQuery()->fetchOne() > 0;
    }

    protected function isValidTaskObject($task): bool
    {
        return (new TaskValidator())->isValid($task);
    }
}
