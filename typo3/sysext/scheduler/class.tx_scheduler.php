<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Christian Jul Jensen <julle@typo3.org>
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
 * @author	François Suter <francois@typo3.org>
 * @author	Christian Jul Jensen <julle@typo3.org>
 *
 * @package		TYPO3
 * @subpackage	tx_scheduler
 */

class tx_scheduler implements t3lib_Singleton {
	/**
	 * @var	array		$extConf: settings from the extension manager
	 */
	 var $extConf = array();

	/**
	 * Constructor, makes sure all derived client classes are included
	 *
	 * @return	void
	 */
	public function __construct() {
			// Get configuration from the extension manager
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scheduler']);
		if (empty($this->extConf['maxLifetime'])) {
			$this->extConf['maxLifetime'] = 1440;
		}

			// Clean up the serialized execution arrays
		$this->cleanExecutionArrays();
	}

	/**
	 * Adds a task to the pool
 	 *
	 * @param	tx_scheduler_Task	$task: the object representing the task to add
	 * @param	string				$identifier: the identified of the task
	 * @return	boolean				TRUE if the task was successfully added, FALSE otherwise
	 */
	public function addTask(tx_scheduler_Task $task) {
		$taskUid = $task->getTaskUid();
		if (empty($taskUid)) {
			$fields = array(
				'crdate' => $GLOBALS['EXEC_TIME'],
				'classname' => get_class($task),
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
	 * @return	void
	 */
	protected function cleanExecutionArrays() {
		$tstamp = $GLOBALS['EXEC_TIME'];

			// Select all tasks with executions
			// NOTE: this cleanup is done for disabled tasks too,
			// to avoid leaving old executions lying around
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, classname, serialized_executions',
			'tx_scheduler_task',
			'serialized_executions != \'\''
		);

		$maxDuration = $this->extConf['maxLifetime'] * 60;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (($serialized_executions = unserialize($row['serialized_executions']))) {
				$executions = array();
				foreach ($serialized_executions AS $task) {
					if (($tstamp - $task) < $maxDuration) {
						$executions[] = $task;
					} else {
						$logMessage = 'Removing logged execution, assuming that the process is dead. Execution of \'' . $row['classname'] . '\' (UID: ' . $row['uid']. ') was started at '.date('Y-m-d H:i:s', $task);
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

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tx_scheduler_task',
					'uid = ' . intval($row['uid']),
					array('serialized_executions' => $value)
				);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
     * This method executes the given task and properly marks and records that execution
	 * It is expected to return FALSE if the task was barred from running or if it was not saved properly
	 *
	 * @param	tx_scheduler_Task	$task: the task to execute
	 * @return	boolean				Whether the task was saved succesfully to the database or not
	 */
	public function executeTask(tx_scheduler_Task $task) {
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

			// Task isn't running or multiple executions are allowed
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
					throw new tx_scheduler_FailedExecutionException(
						'Task failed to execute successfully. Class: ' . get_class($task) . ', UID: ' . $task->getTaskUid(),
						1250596541
					);
				}
			} catch(Exception $e) {
					// Store exception, so that it can be saved to database
				$failure = $e;
			}

				// Unregister excution
			$task->unmarkExecution($executionID, $failure);

				// Log completion of execution
			$logMessage = 'Task executed. Class: ' . get_class($task). ', UID: ' . $task->getTaskUid();
			$this->log($logMessage);

				// Now that the result of the task execution has been handled,
				// throw the exception again, if any
			if ($failure instanceof Exception) {
				throw $failure;
			}
		}

		return $result;
	}

	/**
	 * This method stores information about the last run of the Scheduler into the system registry
	 *
	 * @param	string	$type: Type of run (manual or command-line (assumed to be cron))
	 * @return	void
	 */
	public function recordLastRun($type = 'cron') {
			// Validate input value
		if ($type != 'manual') {
			$type = 'cron';
		}

		/**
		 * @var	t3lib_Registry
		 */
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$runInformation = array('start' => $GLOBALS['EXEC_TIME'], 'end' => time(), 'type' => $type);
		$registry->set('tx_scheduler', 'lastRun', $runInformation);
	}

	/**
	 * Removes a task completely from the system.
	 * TODO: find a way to actually kill the existing jobs
	 *
	 * @param	tx_scheduler_Task	$task: the object representing the task to delete
	 * @return	boolean				TRUE if task was successfully deleted, FALSE otherwise
	 */
	public function removeTask(tx_scheduler_Task $task) {
		$taskUid = $task->getTaskUid();
		if (!empty($taskUid)) {
			return $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_scheduler_task', 'uid = ' . $taskUid);
		} else {
			return FALSE;
		}
	}

	/**
	 * Updates a task in the pool
 	 *
	 * @param	tx_scheduler_Task	$task: Scheduler task object
	 * @return	boolean				False if submitted task was not of proper class
	 */
	public function saveTask(tx_scheduler_Task $task) {
		$taskUid = $task->getTaskUid();
		if (!empty($taskUid)) {
			try {
				$executionTime = $task->getNextDueExecution();
				$task->setExecutionTime($executionTime);
			}
			catch (Exception $e) {
				$task->setDisabled(TRUE);
				$executionTime = 0;
			}
			$task->unsetScheduler();
			$fields = array(
				'nextexecution' => $executionTime,
				'classname' => get_class($task),
				'disable' => $task->isDisabled(),
				'serialized_task_object' => serialize($task)
			);
			return $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', "uid = '" . $taskUid . "'", $fields);
		} else {
			return FALSE;
		}
	}

	/**
	 * Fetches and unserializes a task object from the db. If an uid is given the object
	 * with the uid is returned, else the object representing the next due task is returned.
	 * If there are no due tasks the method throws an exception.
	 *
	 * @param	integer				$uid: primary key of a task
	 * @return	tx_scheduler_Task	The fetched task object
	 */
	public function fetchTask($uid = 0) {
		$whereClause = '';
			// Define where clause
			// If no uid is given, take any non-disabled task which has a next execution time in the past
		if (empty($uid)) {
			$whereClause = 'disable = 0 AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'];
		} else {
			$whereClause = 'uid = ' . intval($uid);
		}

		$queryArray = array(
			'SELECT'	=> 'uid, serialized_task_object',
			'FROM'		=> 'tx_scheduler_task',
			'WHERE'		=> $whereClause,
			'LIMIT'		=> 1
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArray);

			// If there are no available tasks, thrown an exception
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			throw new OutOfBoundsException('No task', 1247827244);

			// Otherwise unserialize the task and return it
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$task = unserialize($row['serialized_task_object']);

			if ($this->isValidTaskObject($task)) {
				// The task is valid, return it

				$task->setScheduler();

			} else {
				// Forcibly set the disable flag to 1 in the database,
				// so that the task does not come up again and again for execution

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', "uid = '" . $row['uid'] . "'", array('disable' => 1));
					// Throw an exception to raise the problem
				throw new UnexpectedValueException('Could not unserialize task', 1255083671);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $task;
	}

	 /**
	  * This method is used to get the database record for a given task
	  * It returns the database record and not the task object
	  *
	  * @param	integer	$uid: primary key of the task to get
	  * @return	array	Database record for the task
	  * @see	tx_scheduler::fetchTask()
	  */
	 public function fetchTaskRecord($uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_scheduler_task', "uid = '" . intval($uid) . "'");

			// If the task is not found, throw an exception
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			throw new OutOfBoundsException('No task', 1247827244);

			// Otherwise get the task's record
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
	 * @param	string		$where: part of a SQL where clause (without the "WHERE" keyword)
	 * @param	boolean		$includeDisabledTasks: TRUE if disabled tasks should be fetched too, FALSE otherwise
	 * @return	array		List of task objects
	 */
	public function fetchTasksWithCondition($where, $includeDisabledTasks = FALSE) {
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
	 * @param	object		The object to test
	 * @return	boolean		TRUE if object is a task, FALSE otherwise
	 */
	public function isValidTaskObject($task) {
		return $task instanceof tx_scheduler_Task;
	}

	/**
	 * This is a utility method that writes some message to the BE Log
	 * It could be expanded to write to some other log
	 *
	 * @param	string		The message to write to the log
	 * @param	integer		Status (0 = message, 1 = error)
	 * @param	mixed		Key for the message
	 * @return	void
	 */
	public function log($message, $status = 0, $code = 'scheduler') {
			// Log only if enabled
		if (!empty($this->extConf['enableBELog'])) {
			$GLOBALS['BE_USER']->writelog(
				4,
				0,
				$status,
				$code,
				'[scheduler]: ' . $message,
				array()
			);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/class.tx_scheduler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/class.tx_scheduler.php']);
}


?>