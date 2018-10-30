<?php
namespace TYPO3\CMS\Scheduler;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Scheduler. This class handles scheduling and execution of tasks.
 */
class Scheduler implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array $extConf Settings from the extension manager
     */
    public $extConf = [];

    /**
     * Constructor, makes sure all derived client classes are included
     */
    public function __construct()
    {
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
     */
    public function addTask(Task\AbstractTask $task)
    {
        $taskUid = $task->getTaskUid();
        if (empty($taskUid)) {
            $fields = [
                'crdate' => $GLOBALS['EXEC_TIME'],
                'disable' => (int)$task->isDisabled(),
                'description' => $task->getDescription(),
                'task_group' => $task->getTaskGroup(),
                'serialized_task_object' => 'RESERVED'
            ];
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_scheduler_task');
            $result = $connection->insert(
                'tx_scheduler_task',
                $fields,
                ['serialized_task_object' => Connection::PARAM_LOB]
            );

            if ($result) {
                $task->setTaskUid($connection->lastInsertId('tx_scheduler_task'));
                $task->save();
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
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
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute();
        $maxDuration = $this->extConf['maxLifetime'] * 60;
        while ($row = $result->fetch()) {
            $executions = [];
            if ($serialized_executions = unserialize($row['serialized_executions'])) {
                foreach ($serialized_executions as $task) {
                    if ($tstamp - $task < $maxDuration) {
                        $executions[] = $task;
                    } else {
                        $task = unserialize($row['serialized_task_object']);
                        $this->log('Removing logged execution, assuming that the process is dead. Execution of \'' . get_class($task) . '\' (UID: ' . $row['uid'] . ') was started at ' . date('Y-m-d H:i:s', $task->getExecutionTime()));
                    }
                }
            }
            $executionCount = count($executions);
            if (count($serialized_executions) !== $executionCount) {
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
     * @throws FailedExecutionException
     * @throws \Exception
     */
    public function executeTask(Task\AbstractTask $task)
    {
        $task->setRunOnNextCronJob(false);
        // Trigger the saving of the task, as this will calculate its next execution time
        // This should be calculated all the time, even if the execution is skipped
        // (in case it is skipped, this pushes back execution to the next possible date)
        $task->save();
        // Set a scheduler object for the task again,
        // as it was removed during the save operation
        $task->setScheduler();
        $result = true;
        // Task is already running and multiple executions are not allowed
        if (!$task->areMultipleExecutionsAllowed() && $task->isExecutionRunning()) {
            // Log multiple execution error
            $logMessage = 'Task is already running and multiple executions are not allowed, skipping! Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid();
            $this->logger->info($logMessage);
            $result = false;
        } else {
            // Log scheduler invocation
            $this->logger->info('Start execution. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid());
            // Register execution
            $executionID = $task->markExecution();
            $failure = null;
            try {
                // Execute task
                $successfullyExecuted = $task->execute();
                if (!$successfullyExecuted) {
                    throw new FailedExecutionException('Task failed to execute successfully. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid(), 1250596541);
                }
            } catch (\Exception $e) {
                // Store exception, so that it can be saved to database
                $failure = $e;
            }
            // Un-register execution
            $task->unmarkExecution($executionID, $failure);
            // Log completion of execution
            $this->logger->info('Task executed. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid());
            // Now that the result of the task execution has been handled,
            // throw the exception again, if any
            if ($failure instanceof \Exception) {
                throw $failure;
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
        /** @var Registry $registry */
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
     */
    public function removeTask(Task\AbstractTask $task)
    {
        $taskUid = $task->getTaskUid();
        if (!empty($taskUid)) {
            $result = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_scheduler_task')
                ->update('tx_scheduler_task', ['deleted' => 1], ['uid' => $taskUid]);
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Updates a task in the pool
     *
     * @param Task\AbstractTask $task Scheduler task object
     * @return bool False if submitted task was not of proper class
     */
    public function saveTask(Task\AbstractTask $task)
    {
        $result = true;
        $taskUid = $task->getTaskUid();
        if (!empty($taskUid)) {
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
                'serialized_task_object' => serialize($task)
            ];
            try {
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('tx_scheduler_task')
                    ->update(
                        'tx_scheduler_task',
                        $fields,
                        ['uid' => $taskUid],
                        ['serialized_task_object' => Connection::PARAM_LOB]
                    );
            } catch (\Doctrine\DBAL\DBALException $e) {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Fetches and unserializes a task object from the db. If an uid is given the object
     * with the uid is returned, else the object representing the next due task is returned.
     * If there are no due tasks the method throws an exception.
     *
     * @param int $uid Primary key of a task
     * @return Task\AbstractTask The fetched task object
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function fetchTask($uid = 0)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_scheduler_task');

        $queryBuilder->select('t.uid', 't.serialized_task_object')
            ->from('tx_scheduler_task', 't')
            ->setMaxResults(1);
        // Define where clause
        // If no uid is given, take any non-disabled task which has a next execution time in the past
        if (empty($uid)) {
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->leftJoin(
                't',
                'tx_scheduler_task_group',
                'g',
                $queryBuilder->expr()->eq('t.task_group', $queryBuilder->quoteIdentifier('g.uid'))
            );
            $queryBuilder->where(
                $queryBuilder->expr()->eq('t.disable', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('t.nextexecution', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lte(
                    't.nextexecution',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('g.hidden', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->isNull('g.hidden')
                ),
                $queryBuilder->expr()->eq('t.deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
            $queryBuilder->orderBy('t.nextexecution', 'ASC');
        } else {
            $queryBuilder->where(
                $queryBuilder->expr()->eq('t.uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('t.deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
        }

        $row = $queryBuilder->execute()->fetch();
        if ($row === false) {
            throw new \OutOfBoundsException('Query could not be executed. Possible defect in tables tx_scheduler_task or tx_scheduler_task_group or DB server problems', 1422044826);
        }
        if (empty($row)) {
            // If there are no available tasks, thrown an exception
            throw new \OutOfBoundsException('No task', 1247827244);
        }
        /** @var Task\AbstractTask $task */
        $task = unserialize($row['serialized_task_object']);
        if ($this->isValidTaskObject($task)) {
            // The task is valid, return it
            $task->setScheduler();
        } else {
            // Forcibly set the disable flag to 1 in the database,
            // so that the task does not come up again and again for execution
            $connectionPool->getConnectionForTable('tx_scheduler_task')->update(
                    'tx_scheduler_task',
                    ['disable' => 1],
                    ['uid' => (int)$row['uid']]
                );
            // Throw an exception to raise the problem
            throw new \UnexpectedValueException('Could not unserialize task', 1255083671);
        }

        return $task;
    }

    /**
     * This method is used to get the database record for a given task
     * It returns the database record and not the task object
     *
     * @param int $uid Primary key of the task to get
     * @return array Database record for the task
     * @see \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
     * @throws \OutOfBoundsException
     */
    public function fetchTaskRecord($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');
        $row = $queryBuilder->select('*')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();

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
     */
    public function fetchTasksWithCondition($where, $includeDisabledTasks = false)
    {
        $tasks = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');

        $queryBuilder
            ->select('serialized_task_object')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );

        if (!$includeDisabledTasks) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
        }

        if (!empty($where)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($where));
        }

        $result = $queryBuilder->execute();
        while ($row = $result->fetch()) {
            /** @var Task\AbstractTask $task */
            $task = unserialize($row['serialized_task_object']);
            // Add the task to the list only if it is valid
            if ($this->isValidTaskObject($task)) {
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
     */
    public function isValidTaskObject($task)
    {
        return $task instanceof Task\AbstractTask && get_class($task->getExecution()) !== '__PHP_Incomplete_Class';
    }

    /**
     * This is a utility method that writes some message to the BE Log
     * It could be expanded to write to some other log
     *
     * @param string $message The message to write to the log
     * @param int $status Status (0 = message, 1 = error)
     * @param mixed $code Key for the message
     */
    public function log($message, $status = 0, $code = '')
    {
        // this method could be called from the constructor (via "cleanExecutionArrays") and no logger is instantiated
        // by then, that's why check if the logger is available
        if (!($this->logger instanceof LoggerInterface)) {
            $this->setLogger(GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__));
        }
        $message = trim('[scheduler]: ' . $code) . ' - ' . $message;
        switch ((int)$status) {
            // error (user problem)
            case 1:
                $this->logger->alert($message);
                break;
            // System Error (which should not happen)
            case 2:
                $this->logger->error($message);
                break;
            // security notice (admin)
            case 3:
                $this->logger->emergency($message);
                break;
            // regular message (= 0)
            default:
                $this->logger->info($message);
        }
    }
}
