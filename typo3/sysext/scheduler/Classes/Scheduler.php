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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * TYPO3 Scheduler. This class handles scheduling and execution of tasks.
 * Formerly known as "Gabriel TYPO3 arch angel"
 */
class Scheduler implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array $extConf Settings from the extension manager
     */
    public $extConf = [];

    /**
     * Constructor, makes sure all derived client classes are included
     *
     * @return \TYPO3\CMS\Scheduler\Scheduler
     */
    public function __construct()
    {
        // Get configuration from the extension manager
        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scheduler']);
        if (empty($this->extConf['maxLifetime'])) {
            $this->extConf['maxLifetime'] = 1440;
        }
        if (empty($this->extConf['useAtdaemon'])) {
            $this->extConf['useAtdaemon'] = 0;
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
                'disable' => $task->isDisabled(),
                'description' => $task->getDescription(),
                'task_group' => $task->getTaskGroup(),
                'serialized_task_object' => 'RESERVED'
            ];
            $result = $this->getDatabaseConnection()->exec_INSERTquery('tx_scheduler_task', $fields);
            if ($result) {
                $task->setTaskUid($this->getDatabaseConnection()->sql_insert_id());
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
     *
     * @return void
     */
    protected function cleanExecutionArrays()
    {
        $tstamp = $GLOBALS['EXEC_TIME'];
        $db = $this->getDatabaseConnection();
        // Select all tasks with executions
        // NOTE: this cleanup is done for disabled tasks too,
        // to avoid leaving old executions lying around
        $res = $db->exec_SELECTquery('uid, serialized_executions, serialized_task_object', 'tx_scheduler_task', 'serialized_executions <> \'\'');
        $maxDuration = $this->extConf['maxLifetime'] * 60;
        while ($row = $db->sql_fetch_assoc($res)) {
            $executions = [];
            if ($serialized_executions = unserialize($row['serialized_executions'])) {
                foreach ($serialized_executions as $task) {
                    if ($tstamp - $task < $maxDuration) {
                        $executions[] = $task;
                    } else {
                        $task = unserialize($row['serialized_task_object']);
                        $logMessage = 'Removing logged execution, assuming that the process is dead. Execution of \'' . get_class($task) . '\' (UID: ' . $row['uid'] . ') was started at ' . date('Y-m-d H:i:s', $task->getExecutionTime());
                        $this->log($logMessage);
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
                $db->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . (int)$row['uid'], ['serialized_executions' => $value]);
            }
        }
        $db->sql_free_result($res);
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
            $this->log($logMessage);
            $result = false;
        } else {
            // Log scheduler invocation
            $logMessage = 'Start execution. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid();
            $this->log($logMessage);
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
            // make sure database-connection is fine
            // for long-running tasks the database might meanwhile have disconnected
            $this->getDatabaseConnection()->isConnected();
            // Un-register execution
            $task->unmarkExecution($executionID, $failure);
            // Log completion of execution
            $logMessage = 'Task executed. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid();
            $this->log($logMessage);
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
     * @return void
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
            $result = $this->getDatabaseConnection()->exec_DELETEquery('tx_scheduler_task', 'uid = ' . $taskUid);
        } else {
            $result = false;
        }
        if ($result) {
            $this->scheduleNextSchedulerRunUsingAtDaemon();
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
        $taskUid = $task->getTaskUid();
        if (!empty($taskUid)) {
            try {
                $executionTime = $task->getNextDueExecution();
                $task->setExecutionTime($executionTime);
            } catch (\Exception $e) {
                $task->setDisabled(true);
                $executionTime = 0;
            }
            $task->unsetScheduler();
            $fields = [
                'nextexecution' => $executionTime,
                'disable' => $task->isDisabled(),
                'description' => $task->getDescription(),
                'task_group' => $task->getTaskGroup(),
                'serialized_task_object' => serialize($task)
            ];
            $result = $this->getDatabaseConnection()->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . $taskUid, $fields);
        } else {
            $result = false;
        }
        if ($result) {
            $this->scheduleNextSchedulerRunUsingAtDaemon();
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
        // Define where clause
        // If no uid is given, take any non-disabled task which has a next execution time in the past
        if (empty($uid)) {
            $queryArray = [
                'SELECT' => 'tx_scheduler_task.uid AS uid, serialized_task_object',
                'FROM' => 'tx_scheduler_task LEFT JOIN tx_scheduler_task_group ON tx_scheduler_task.task_group = tx_scheduler_task_group.uid',
                'WHERE' => 'disable = 0 AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'] . ' AND (tx_scheduler_task_group.hidden = 0 OR tx_scheduler_task_group.hidden IS NULL)',
                'LIMIT' => 1
            ];
        } else {
            $queryArray = [
                'SELECT' => 'uid, serialized_task_object',
                'FROM' => 'tx_scheduler_task',
                'WHERE' => 'uid = ' . (int)$uid,
                'LIMIT' => 1
            ];
        }

        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECT_queryArray($queryArray);
        if ($res === false) {
            throw new \OutOfBoundsException('Query could not be executed. Possible defect in tables tx_scheduler_task or tx_scheduler_task_group or DB server problems', 1422044826);
        }
        // If there are no available tasks, thrown an exception
        if ($db->sql_num_rows($res) == 0) {
            throw new \OutOfBoundsException('No task', 1247827244);
        } else {
            $row = $db->sql_fetch_assoc($res);
            /** @var $task Task\AbstractTask */
            $task = unserialize($row['serialized_task_object']);
            if ($this->isValidTaskObject($task)) {
                // The task is valid, return it
                $task->setScheduler();
            } else {
                // Forcibly set the disable flag to 1 in the database,
                // so that the task does not come up again and again for execution
                $db->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . $row['uid'], ['disable' => 1]);
                // Throw an exception to raise the problem
                throw new \UnexpectedValueException('Could not unserialize task', 1255083671);
            }
            $db->sql_free_result($res);
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
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery('*', 'tx_scheduler_task', 'uid = ' . (int)$uid);
        // If the task is not found, throw an exception
        if ($db->sql_num_rows($res) == 0) {
            throw new \OutOfBoundsException('No task', 1247827245);
        } else {
            $row = $db->sql_fetch_assoc($res);
            $db->sql_free_result($res);
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
        $whereClause = '';
        $tasks = [];
        if (!empty($where)) {
            $whereClause = $where;
        }
        if (!$includeDisabledTasks) {
            if (!empty($whereClause)) {
                $whereClause .= ' AND ';
            }
            $whereClause .= 'disable = 0';
        }
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery('serialized_task_object', 'tx_scheduler_task', $whereClause);
        if ($res) {
            while ($row = $db->sql_fetch_assoc($res)) {
                /** @var Task\AbstractTask $task */
                $task = unserialize($row['serialized_task_object']);
                // Add the task to the list only if it is valid
                if ($this->isValidTaskObject($task)) {
                    $task->setScheduler();
                    $tasks[] = $task;
                }
            }
            $db->sql_free_result($res);
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
        return $task instanceof Task\AbstractTask;
    }

    /**
     * This is a utility method that writes some message to the BE Log
     * It could be expanded to write to some other log
     *
     * @param string $message The message to write to the log
     * @param int $status Status (0 = message, 1 = error)
     * @param mixed $code Key for the message
     * @return void
     */
    public function log($message, $status = 0, $code = 'scheduler')
    {
        // Log only if enabled
        if (!empty($this->extConf['enableBELog'])) {
            $GLOBALS['BE_USER']->writelog(4, 0, $status, $code, '[scheduler]: ' . $message, []);
        }
    }

    /**
     * Schedule the next run of scheduler
     * For the moment only the "at"-daemon is used, and only if it is enabled
     *
     * @return bool Successfully scheduled next execution using "at"-daemon
     * @see tx_scheduler::fetchTask()
     */
    public function scheduleNextSchedulerRunUsingAtDaemon()
    {
        if ((int)$this->extConf['useAtdaemon'] !== 1) {
            return false;
        }
        /** @var $registry Registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        // Get at job id from registry and remove at job
        $atJobId = $registry->get('tx_scheduler', 'atJobId');
        if (MathUtility::canBeInterpretedAsInteger($atJobId)) {
            shell_exec('atrm ' . (int)$atJobId . ' 2>&1');
        }
        // Can not use fetchTask() here because if tasks have just executed
        // they are not in the list of next executions
        $tasks = $this->fetchTasksWithCondition('');
        $nextExecution = false;
        foreach ($tasks as $task) {
            try {
                /** @var $task Task\AbstractTask */
                $tempNextExecution = $task->getNextDueExecution();
                if ($nextExecution === false || $tempNextExecution < $nextExecution) {
                    $nextExecution = $tempNextExecution;
                }
            } catch (\OutOfBoundsException $e) {
                // The event will not be executed again or has already ended - we don't have to consider it for
                // scheduling the next "at" run
            }
        }
        if ($nextExecution !== false) {
            if ($nextExecution > $GLOBALS['EXEC_TIME']) {
                $startTime = strftime('%H:%M %F', $nextExecution);
            } else {
                $startTime = 'now+1minute';
            }
            $cliDispatchPath = PATH_site . 'typo3/cli_dispatch.phpsh';
            list($cliDispatchPathEscaped, $startTimeEscaped) =
                CommandUtility::escapeShellArguments([$cliDispatchPath, $startTime]);
            $cmd = 'echo ' . $cliDispatchPathEscaped . ' scheduler | at ' . $startTimeEscaped . ' 2>&1';
            $output = shell_exec($cmd);
            $outputParts = '';
            foreach (explode(LF, $output) as $outputLine) {
                if (GeneralUtility::isFirstPartOfStr($outputLine, 'job')) {
                    $outputParts = explode(' ', $outputLine, 3);
                    break;
                }
            }
            if ($outputParts[0] === 'job' && MathUtility::canBeInterpretedAsInteger($outputParts[1])) {
                $atJobId = (int)$outputParts[1];
                $registry->set('tx_scheduler', 'atJobId', $atJobId);
            }
        }
        return true;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
