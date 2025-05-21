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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;

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
    public array $extConf = [];

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
        $result = $queryBuilder->select('*')
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
                            $schedulerTask = $this->taskSerializer->deserialize($row);
                            $taskType = $schedulerTask->getTaskType();
                            $executionTime = date('Y-m-d H:i:s', $schedulerTask->getExecutionTime());
                        } catch (InvalidTaskException $e) {
                            $taskType = 'unknown type';
                            $executionTime = 'unknown time';
                        }
                        $this->logger->info(
                            'Removing logged execution, assuming that the process is dead. Execution of  \'{taskType} \' (UID: {taskId}) was started at {executionTime}',
                            [
                                'taskType' => $taskType,
                                'taskId' => $row['uid'],
                                'executionTime' => $executionTime,
                            ]
                        );
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
    public function executeTask(AbstractTask $task): bool
    {
        $task->setRunOnNextCronJob(false);
        // Trigger the saving of the task, as this will calculate its next execution time
        // This should be calculated all the time, even if the execution is skipped
        // (in case it is skipped, this pushes back execution to the next possible date)
        $this->schedulerTaskRepository->updateExecution($task, $task->getExecution()->isSingleRun());

        // Reserve an id for the upcoming execution
        $executionID = $this->schedulerTaskRepository->addExecutionToTask($task);
        // Make sure we're the only one executing a single-execution-only task
        if (!$task->getExecution()?->isParallelExecutionAllowed() && $executionID > 0) {
            $this->schedulerTaskRepository->removeExecutionOfTask($task, $executionID);
            $this->logger->info('Task is already running and multiple executions are not allowed, skipping! Task Type: {taskType}, UID: {uid}', [
                'taskType' => $task->getTaskType(),
                'uid' => $task->getTaskUid(),
            ]);
            return false;
        }

        // Log scheduler invocation
        $this->logger->info('Start execution. Task Type: {taskType}, UID: {uid}', [
            'taskType' => $task->getTaskType(),
            'uid' => $task->getTaskUid(),
        ]);

        $failureString = '';
        try {
            // Execute task
            $successfullyExecuted = $task->execute();
            if (!$successfullyExecuted) {
                throw new FailedExecutionException('Task failed to execute successfully. Task Type: ' . $task->getTaskType() . ', UID: ' . $task->getTaskUid(), 1250596541);
            }
            return true;
        } catch (\Throwable $e) {
            // Log failed execution
            $this->logger->error('Task failed to execute successfully. Task Type: {taskType}, UID: {taskId}, Code: {code}, "{message}" in {exceptionFile} at line {exceptionLine}', [
                'taskType' => $task->getTaskType(),
                'taskId' => $task->getTaskUid(),
                'exception' => $e,
                'exceptionFile' => $e->getFile(),
                'exceptionLine' => $e->getLine(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
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
            // Now that the result of the task execution has been handled,
            // throw the exception again, if any
            throw $e;
        } finally {
            // Un-register execution
            $this->schedulerTaskRepository->removeExecutionOfTask($task, $executionID, $failureString);
            // Log completion of execution
            $this->logger->info('Task executed. Task Type: {taskType}, UID: {uid}', [
                'taskType' => $task->getTaskType(),
                'uid' => $task->getTaskUid(),
            ]);
        }
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
}
