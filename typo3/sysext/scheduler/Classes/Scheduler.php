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

namespace TYPO3\CMS\Scheduler;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

/**
 * TYPO3 Scheduler. This class handles scheduling and execution of tasks.
 */
class Scheduler implements SingletonInterface
{
    protected LoggerInterface $logger;
    protected TaskSerializer $taskSerializer;
    protected SchedulerTaskRepository $schedulerTaskRepository;

    /**
     * @var array $extConf Settings from the extension manager
     */
    public $extConf = [];

    /**
     * Constructor, makes sure all derived client classes are included
     */
    public function __construct(LoggerInterface $logger, TaskSerializer $taskSerializer, SchedulerTaskRepository $schedulerTaskRepository)
    {
        $this->logger = $logger;
        $this->taskSerializer = $taskSerializer;
        $this->schedulerTaskRepository = $schedulerTaskRepository;
        // Get configuration from the extension manager
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('scheduler');
        if (empty($this->extConf['maxLifetime'])) {
            $this->extConf['maxLifetime'] = 1440;
        }
        // Clean up the serialized execution arrays
        $this->cleanExecutionArrays();
    }

    /**
     * Adds a task to the pool
     *
     * @param Task\AbstractTask $task The object representing the task to add
     * @return bool TRUE if the task was successfully added, FALSE otherwise
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function addTask(AbstractTask $task)
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return $this->schedulerTaskRepository->add($task);
    }

    /**
     * Cleans the execution lists of the scheduled tasks, executions older than 24h are removed
     * @todo find a way to actually kill the job
     */
    protected function cleanExecutionArrays()
    {
        $tstamp = $GLOBALS['EXEC_TIME'];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_scheduler_task');

        // Select all tasks with executions
        // NOTE: this cleanup is done for disabled tasks too,
        // to avoid leaving old executions lying around
        $result = $queryBuilder->select('uid', 'serialized_executions', 'serialized_task_object')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->neq(
                    'serialized_executions',
                    $queryBuilder->createNamedParameter('')
                ),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery();
        $maxDuration = $this->extConf['maxLifetime'] * 60;
        while ($row = $result->fetchAssociative()) {
            $executions = [];
            if ($serialized_executions = unserialize($row['serialized_executions'])) {
                foreach ($serialized_executions as $task) {
                    if ($tstamp - $task < $maxDuration) {
                        $executions[] = $task;
                    } else {
                        try {
                            $schedulerTask = $this->taskSerializer->deserialize($row['serialized_task_object']);
                            $taskClass = get_class($schedulerTask);
                            $executionTime = date('Y-m-d H:i:s', $schedulerTask->getExecutionTime());
                        } catch (InvalidTaskException $e) {
                            $taskClass = 'unknown class';
                            $executionTime = 'unknown time';
                        }
                        $this->log('Removing logged execution, assuming that the process is dead. Execution of \'' . $taskClass . '\' (UID: ' . $row['uid'] . ') was started at ' . $executionTime);
                    }
                }
            }
            $executionCount = count($executions);
            if (!is_array($serialized_executions) || count($serialized_executions) !== $executionCount) {
                if ($executionCount === 0) {
                    $value = '';
                } else {
                    $value = serialize($executions);
                }
                $connectionPool->getConnectionForTable('tx_scheduler_task')->update(
                    'tx_scheduler_task',
                    ['serialized_executions' => $value],
                    ['uid' => (int)$row['uid']],
                    ['serialized_executions' => Connection::PARAM_LOB]
                );
            }
        }
    }

    /**
     * This method executes the given task and properly marks and records that execution
     * It is expected to return FALSE if the task was barred from running or if it was not saved properly
     *
     * @param Task\AbstractTask $task The task to execute
     * @return bool Whether the task was saved successfully to the database or not
     * @throws \Throwable
     */
    public function executeTask(AbstractTask $task)
    {
        $task->setRunOnNextCronJob(false);
        // Trigger the saving of the task, as this will calculate its next execution time
        // This should be calculated all the time, even if the execution is skipped
        // (in case it is skipped, this pushes back execution to the next possible date)
        $this->schedulerTaskRepository->update($task);
        // Set a scheduler object for the task again,
        // as it was removed during the save operation
        $task->setScheduler();
        $result = true;
        // Task is already running and multiple executions are not allowed
        if (!$task->areMultipleExecutionsAllowed() && $this->schedulerTaskRepository->isTaskMarkedAsRunning($task)) {
            // Log multiple execution error
            $this->logger->info('Task is already running and multiple executions are not allowed, skipping! Class: {class}, UID: {uid}', [
                'class' => get_class($task),
                'uid' => $task->getTaskUid(),
            ]);
            $result = false;
        } else {
            // Log scheduler invocation
            $this->logger->info('Start execution. Class: {class}, UID: {uid}', [
                'class' => get_class($task),
                'uid' => $task->getTaskUid(),
            ]);
            // Register execution
            $executionID = $this->schedulerTaskRepository->addExecutionToTask($task);
            $failureString = '';
            $e = null;
            try {
                // Execute task
                $successfullyExecuted = $task->execute();
                if (!$successfullyExecuted) {
                    throw new FailedExecutionException('Task failed to execute successfully. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid(), 1250596541);
                }
            } catch (\Throwable $e) {
                // Log failed execution
                $this->logger->error('Task failed to execute successfully. Class: {class}, UID: {uid}', [
                    'class' => get_class($task),
                    'uid' => $task->getTaskUid(),
                    'exception' => $e,
                ]);
                // Store exception, so that it can be saved to database
                // Do not serialize the complete exception or the trace, this can lead to huge strings > 50MB
                $failureString = serialize([
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'traceString' => $e->getTraceAsString(),
                ]);
            }
            // Un-register execution
            $this->schedulerTaskRepository->removeExecutionOfTask($task, $executionID, $failureString);
            // Log completion of execution
            $this->logger->info('Task executed. Class: {class}, UID: {uid}', [
                'class' => get_class($task),
                'uid' => $task->getTaskUid(),
            ]);
            // Now that the result of the task execution has been handled,
            // throw the exception again, if any
            if ($e !== null) {
                throw $e;
            }
        }
        return $result;
    }

    /**
     * This method stores information about the last run of the Scheduler into the system registry
     *
     * @param string $type Type of run (manual or command-line (assumed to be cron))
     */
    public function recordLastRun($type = 'cron')
    {
        // Validate input value
        if ($type !== 'manual' && $type !== 'cli-by-id') {
            $type = 'cron';
        }
        $registry = GeneralUtility::makeInstance(Registry::class);
        $runInformation = ['start' => $GLOBALS['EXEC_TIME'], 'end' => time(), 'type' => $type];
        $registry->set('tx_scheduler', 'lastRun', $runInformation);
    }

    /**
     * Removes a task completely from the system.
     *
     * @todo find a way to actually kill the existing jobs
     *
     * @param Task\AbstractTask $task The object representing the task to delete
     * @return bool TRUE if task was successfully deleted, FALSE otherwise
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function removeTask(AbstractTask $task)
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return $this->schedulerTaskRepository->remove($task);
    }

    /**
     * Update a task in the pool.
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function saveTask(AbstractTask $task): bool
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return $this->schedulerTaskRepository->update($task);
    }

    /**
     * Fetches and unserializes a task object from the db. If a uid is given the object
     * with the uid is returned, else the object representing the next due task is returned.
     * If there are no due tasks the method throws an exception.
     *
     * @param int $uid Primary key of a task
     * @return Task\AbstractTask|null The fetched task object
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function fetchTask($uid = 0): ?AbstractTask
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        if ($uid > 0) {
            return $this->schedulerTaskRepository->findByUid((int)$uid);
        }
        return $this->schedulerTaskRepository->findNextExecutableTask();
    }

    /**
     * This method is used to get the database record for a given task
     * It returns the database record and not the task object
     *
     * @param int $uid Primary key of the task to get
     * @return array Database record for the task
     * @see \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
     * @throws \OutOfBoundsException
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function fetchTaskRecord($uid)
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        $row = $this->schedulerTaskRepository->findRecordByUid((int)$uid);
        // If the task is not found, throw an exception
        if (empty($row)) {
            throw new \OutOfBoundsException('No task', 1247827245);
        }
        return $row;
    }

    /**
     * Fetches and unserializes task objects selected with some (SQL) condition
     * Objects are returned as an array
     *
     * @param string $where Part of a SQL where clause (without the "WHERE" keyword)
     * @param bool $includeDisabledTasks TRUE if disabled tasks should be fetched too, FALSE otherwise
     * @return array List of task objects
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function fetchTasksWithCondition($where, $includeDisabledTasks = false)
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        $tasks = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');

        $queryBuilder
            ->select('serialized_task_object')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        if (!$includeDisabledTasks) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
        }

        if (!empty($where)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($where));
        }

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            try {
                $task = $this->taskSerializer->deserialize($row['serialized_task_object']);
            } catch (InvalidTaskException) {
                continue;
            }

            // Add the task to the list only if it is valid
            if ((new TaskValidator())->isValid($task)) {
                $task->setScheduler();
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    /**
     * This method encapsulates a very simple test for the purpose of clarity.
     * Registered tasks are stored in the database along with a serialized task object.
     * When a registered task is fetched, its object is unserialized.
     * At that point, if the class corresponding to the object is not available anymore
     * (e.g. because the extension providing it has been uninstalled),
     * the unserialization will produce an incomplete object.
     * This test checks whether the unserialized object is of the right (parent) class or not.
     *
     * @param object $task The object to test
     * @return bool TRUE if object is a task, FALSE otherwise
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function isValidTaskObject($task)
    {
        trigger_error('Scheduler->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return (new TaskValidator())->isValid($task);
    }

    /**
     * This is a utility method that writes some message to the BE Log
     * It could be expanded to write to some other log
     *
     * @param string $message The message to write to the log
     * @param int $status Status (0 = message, 1 = error)
     * @param mixed $code Key for the message
     * @internal
     */
    public function log($message, $status = 0, $code = '')
    {
        $messageTemplate = '[scheduler]: {code} - {original_message}';
        // @todo Replace these magic numbers with constants or enums.
        switch ((int)$status) {
            // error (user problem)
            case 1:
                $this->logger->alert($messageTemplate, ['code' => $code, 'original_message' => $message]);
                break;
                // System Error (which should not happen)
            case 2:
                $this->logger->error($messageTemplate, ['code' => $code, 'original_message' => $message]);
                break;
                // security notice (admin)
            case 3:
                $this->logger->emergency($messageTemplate, ['code' => $code, 'original_message' => $message]);
                break;
                // regular message (= 0)
            default:
                $this->logger->info($messageTemplate, ['code' => $code, 'original_message' => $message]);
        }
    }
}
