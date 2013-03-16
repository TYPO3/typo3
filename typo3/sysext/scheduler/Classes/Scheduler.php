<?php
namespace TYPO3\CMS\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Christian Jul Jensen <julle@typo3.org>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * TYPO3 Scheduler. This class handles scheduling and execution of tasks.
 * Formerly known as "Gabriel TYPO3 arch angel"
 *
 * @author 	François Suter <francois@typo3.org>
 * @author 	Christian Jul Jensen <julle@typo3.org>
 */
class Scheduler implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var 	array		$extConf: settings from the extension manager
	 * @todo Define visibility
	 */
	public $extConf = array();

	/**
	 * Constructor, makes sure all derived client classes are included
	 *
	 * @return \TYPO3\CMS\Scheduler\Scheduler
	 */
	public function __construct() {
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
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The object representing the task to add
	 * @return boolean TRUE if the task was successfully added, FALSE otherwise
	 */
	public function addTask(\TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$taskUid = $task->getTaskUid();
		if (empty($taskUid)) {
			$fields = array(
				'crdate' => $GLOBALS['EXEC_TIME'],
				'disable' => $task->isDisabled(),
				'serialized_task_object' => 'RESERVED'
			);
			$result = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_scheduler_task', $fields);
			if ($result) {
				$task->setTaskUid($GLOBALS['TYPO3_DB']->sql_insert_id());
				$task->save();
				$result = TRUE;
			} else {
				$result = FALSE;
			}
		} else {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Cleans the execution lists of the scheduled tasks, executions older than 24h are removed
	 * TODO: find a way to actually kill the job
	 *
	 * @return void
	 */
	protected function cleanExecutionArrays() {
		$tstamp = $GLOBALS['EXEC_TIME'];
		// Select all tasks with executions
		// NOTE: this cleanup is done for disabled tasks too,
		// to avoid leaving old executions lying around
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, serialized_executions, serialized_task_object', 'tx_scheduler_task', 'serialized_executions <> \'\'');
		$maxDuration = $this->extConf['maxLifetime'] * 60;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$executions = array();
			if ($serialized_executions = unserialize($row['serialized_executions'])) {
				foreach ($serialized_executions as $task) {
					if ($tstamp - $task < $maxDuration) {
						$executions[] = $task;
					} else {
						$task = unserialize($row['serialized_task_object']);
						$logMessage = 'Removing logged execution, assuming that the process is dead. Execution of \'' . get_class($task) . '\' (UID: ' . $row['uid'] . ') was started at ' . date('Y-m-d H:i:s', $task);
						$this->log($logMessage);
					}
				}
			}
			if (count($serialized_executions) != count($executions)) {
				if (count($executions) == 0) {
					$value = '';
				} else {
					$value = serialize($executions);
				}
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . intval($row['uid']), array('serialized_executions' => $value));
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * This method executes the given task and properly marks and records that execution
	 * It is expected to return FALSE if the task was barred from running or if it was not saved properly
	 *
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task to execute
	 * @return boolean Whether the task was saved successfully to the database or not
	 */
	public function executeTask(\TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		// Trigger the saving of the task, as this will calculate its next execution time
		// This should be calculated all the time, even if the execution is skipped
		// (in case it is skipped, this pushes back execution to the next possible date)
		$task->save();
		// Set a scheduler object for the task again,
		// as it was removed during the save operation
		$task->setScheduler();
		$result = TRUE;
		// Task is already running and multiple executions are not allowed
		if (!$task->areMultipleExecutionsAllowed() && $task->isExecutionRunning()) {
			// Log multiple execution error
			$logMessage = 'Task is already running and multiple executions are not allowed, skipping! Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid();
			$this->log($logMessage);
			$result = FALSE;
		} else {
			// Log scheduler invocation
			$logMessage = 'Start execution. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid();
			$this->log($logMessage);
			// Register execution
			$executionID = $task->markExecution();
			$failure = NULL;
			try {
				// Execute task
				$successfullyExecuted = $task->execute();
				if (!$successfullyExecuted) {
					throw new \TYPO3\CMS\Scheduler\FailedExecutionException('Task failed to execute successfully. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid(), 1250596541);
				}
			} catch (\Exception $e) {
				// Store exception, so that it can be saved to database
				$failure = $e;
			}
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
	public function recordLastRun($type = 'cron') {
		// Validate input value
		if ($type !== 'manual' && $type !== 'cli-by-id') {
			$type = 'cron';
		}
		/** @var $registry \TYPO3\CMS\Core\Registry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$runInformation = array('start' => $GLOBALS['EXEC_TIME'], 'end' => time(), 'type' => $type);
		$registry->set('tx_scheduler', 'lastRun', $runInformation);
	}

	/**
	 * Removes a task completely from the system.
	 * TODO: find a way to actually kill the existing jobs
	 *
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The object representing the task to delete
	 * @return boolean TRUE if task was successfully deleted, FALSE otherwise
	 */
	public function removeTask(\TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$taskUid = $task->getTaskUid();
		if (!empty($taskUid)) {
			$result = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_scheduler_task', 'uid = ' . $taskUid);
		} else {
			$result = FALSE;
		}
		if ($result) {
			$this->scheduleNextSchedulerRunUsingAtDaemon();
		}
		return $result;
	}

	/**
	 * Updates a task in the pool
	 *
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Scheduler task object
	 * @return boolean False if submitted task was not of proper class
	 */
	public function saveTask(\TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$taskUid = $task->getTaskUid();
		if (!empty($taskUid)) {
			try {
				$executionTime = $task->getNextDueExecution();
				$task->setExecutionTime($executionTime);
			} catch (\Exception $e) {
				$task->setDisabled(TRUE);
				$executionTime = 0;
			}
			$task->unsetScheduler();
			$fields = array(
				'nextexecution' => $executionTime,
				'disable' => $task->isDisabled(),
				'serialized_task_object' => serialize($task)
			);
			$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . $taskUid, $fields);
		} else {
			$result = FALSE;
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
	 * @param integer $uid Primary key of a task
	 * @return \TYPO3\CMS\Scheduler\Task\AbstractTask The fetched task object
	 */
	public function fetchTask($uid = 0) {
		// Define where clause
		// If no uid is given, take any non-disabled task which has a next execution time in the past
		if (empty($uid)) {
			$whereClause = 'disable = 0 AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'];
		} else {
			$whereClause = 'uid = ' . intval($uid);
		}
		$queryArray = array(
			'SELECT' => 'uid, serialized_task_object',
			'FROM' => 'tx_scheduler_task',
			'WHERE' => $whereClause,
			'LIMIT' => 1
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArray);
		// If there are no available tasks, thrown an exception
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			throw new \OutOfBoundsException('No task', 1247827244);
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			/** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
			$task = unserialize($row['serialized_task_object']);
			if ($this->isValidTaskObject($task)) {
				// The task is valid, return it
				$task->setScheduler();
			} else {
				// Forcibly set the disable flag to 1 in the database,
				// so that the task does not come up again and again for execution
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . $row['uid'], array('disable' => 1));
				// Throw an exception to raise the problem
				throw new \UnexpectedValueException('Could not unserialize task', 1255083671);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $task;
	}

	/**
	 * This method is used to get the database record for a given task
	 * It returns the database record and not the task object
	 *
	 * @param integer $uid Primary key of the task to get
	 * @return array Database record for the task
	 * @see tx_scheduler::fetchTask()
	 */
	public function fetchTaskRecord($uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_scheduler_task', 'uid = ' . intval($uid));
		// If the task is not found, throw an exception
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			throw new \OutOfBoundsException('No task', 1247827244);
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $row;
	}

	/**
	 * Fetches and unserializes task objects selected with some (SQL) condition
	 * Objects are returned as an array
	 *
	 * @param string $where Part of a SQL where clause (without the "WHERE" keyword)
	 * @param boolean $includeDisabledTasks TRUE if disabled tasks should be fetched too, FALSE otherwise
	 * @return array List of task objects
	 */
	public function fetchTasksWithCondition($where, $includeDisabledTasks = FALSE) {
		$whereClause = '';
		$tasks = array();
		if (!empty($where)) {
			$whereClause = $where;
		}
		if (!$includeDisabledTasks) {
			if (!empty($whereClause)) {
				$whereClause .= ' AND ';
			}
			$whereClause .= 'disable = 0';
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('serialized_task_object', 'tx_scheduler_task', $whereClause);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				/** @var $task Task */
				$task = unserialize($row['serialized_task_object']);
				// Add the task to the list only if it is valid
				if ($this->isValidTaskObject($task)) {
					$task->setScheduler();
					$tasks[] = $task;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
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
	 * @return boolean TRUE if object is a task, FALSE otherwise
	 */
	public function isValidTaskObject($task) {
		return $task instanceof \TYPO3\CMS\Scheduler\Task\AbstractTask;
	}

	/**
	 * This is a utility method that writes some message to the BE Log
	 * It could be expanded to write to some other log
	 *
	 * @param string $message The message to write to the log
	 * @param integer $status Status (0 = message, 1 = error)
	 * @param mixed $code Key for the message
	 * @return void
	 */
	public function log($message, $status = 0, $code = 'scheduler') {
		// Log only if enabled
		if (!empty($this->extConf['enableBELog'])) {
			$GLOBALS['BE_USER']->writelog(4, 0, $status, $code, '[scheduler]: ' . $message, array());
		}
	}

	/**
	 * Schedule the next run of scheduler
	 * For the moment only the "at"-daemon is used, and only if it is enabled
	 *
	 * @return boolean Successfully scheduled next execution using "at"-daemon
	 * @see tx_scheduler::fetchTask()
	 */
	public function scheduleNextSchedulerRunUsingAtDaemon() {
		if ((int) $this->extConf['useAtdaemon'] !== 1) {
			return FALSE;
		}
		/** @var $registry \TYPO3\CMS\Core\Registry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		// Get at job id from registry and remove at job
		$atJobId = $registry->get('tx_scheduler', 'atJobId');
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($atJobId)) {
			shell_exec('atrm ' . (int) $atJobId . ' 2>&1');
		}
		// Can not use fetchTask() here because if tasks have just executed
		// they are not in the list of next executions
		$tasks = $this->fetchTasksWithCondition('');
		$nextExecution = FALSE;
		foreach ($tasks as $task) {
			try {
				/** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
				$tempNextExecution = $task->getNextDueExecution();
				if ($nextExecution === FALSE || $tempNextExecution < $nextExecution) {
					$nextExecution = $tempNextExecution;
				}
			} catch (\OutOfBoundsException $e) {
				// The event will not be executed again or has already ended - we don't have to consider it for
				// scheduling the next "at" run
			}
		}
		if ($nextExecution !== FALSE) {
			if ($nextExecution > $GLOBALS['EXEC_TIME']) {
				$startTime = strftime('%H:%M %F', $nextExecution);
			} else {
				$startTime = 'now+1minute';
			}
			$cliDispatchPath = PATH_site . 'typo3/cli_dispatch.phpsh';
			$currentLocale = setlocale(LC_CTYPE, 0);
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
				setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
			}
			$cmd = 'echo ' . escapeshellarg($cliDispatchPath) . ' scheduler | at ' . escapeshellarg($startTime) . ' 2>&1';
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
				setlocale(LC_CTYPE, $currentLocale);
			}
			$output = shell_exec($cmd);
			$outputParts = '';
			foreach (explode(LF, $output) as $outputLine) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($outputLine, 'job')) {
					$outputParts = explode(' ', $outputLine, 3);
					break;
				}
			}
			if ($outputParts[0] === 'job' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($outputParts[1])) {
				$atJobId = (int) $outputParts[1];
				$registry->set('tx_scheduler', 'atJobId', $atJobId);
			}
		}
		return TRUE;
	}

}


?>