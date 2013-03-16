<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Christian Jul Jensen (julle@typo3.org)
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
 * This is the base class for all Scheduler tasks
 * It's an abstract class, not designed to be instantiated directly
 * All Scheduler tasks should inherit from this class
 *
 * @author 	François Suter <francois@typo3.org>
 * @author 	Christian Jul Jensen <julle@typo3.org>
 */
abstract class AbstractTask {

	/**
	 * Reference to a scheduler object
	 *
	 * @var \TYPO3\CMS\Scheduler\Scheduler
	 */
	protected $scheduler;

	/**
	 * The unique id of the task used to identify it in the database
	 *
	 * @var int
	 */
	protected $taskUid;

	/**
	 * Disable flag, TRUE if task is disabled, FALSE otherwise
	 *
	 * @var 	boolean
	 */
	protected $disabled = FALSE;

	/**
	 * The execution object related to the task
	 *
	 * @var \TYPO3\CMS\Scheduler\Execution
	 */
	protected $execution;

	/**
	 * This variable contains the time of next execution of the task
	 *
	 * @var integer
	 */
	protected $executionTime = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setScheduler();
		$this->execution = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Execution');
	}

	/**
	 * This is the main method that is called when a task is executed
	 * It MUST be implemented by all classes inheriting from this one
	 * Note that there is no error handling, errors and failures are expected
	 * to be handled and logged by the client implementations.
	 * Should return TRUE on successful execution, FALSE on error.
	 *
	 * @return boolean Returns TRUE on successful execution, FALSE on error
	 */
	abstract public function execute();

	/**
	 * This method is designed to return some additional information about the task,
	 * that may help to set it apart from other tasks from the same class
	 * This additional information is used - for example - in the Scheduler's BE module
	 * This method should be implemented in most task classes
	 *
	 * @return string Information to display
	 */
	public function getAdditionalInformation() {
		return '';
	}

	/**
	 * This method is used to set the unique id of the task
	 *
	 * @param integer $id Primary key (from the database record) of the scheduled task
	 * @return void
	 */
	public function setTaskUid($id) {
		$this->taskUid = intval($id);
	}

	/**
	 * This method returns the unique id of the task
	 *
	 * @return integer The id of the task
	 */
	public function getTaskUid() {
		return $this->taskUid;
	}

	/**
	 * This method returns the title of the scheduler task
	 *
	 * @return string
	 */
	public function getTaskTitle() {
		return $GLOBALS['LANG']->sL($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][get_class($this)]['title']);
	}

	/**
	 * This method returns the description of the scheduler task
	 *
	 * @return string
	 */
	public function getTaskDescription() {
		return $GLOBALS['LANG']->sL($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][get_class($this)]['description']);
	}

	/**
	 * This method returns the class name of the scheduler task
	 *
	 * @return string
	 */
	public function getTaskClassName() {
		return get_class($this);
	}

	/**
	 * This method returns the disable status of the task
	 *
	 * @return boolean TRUE if task is disabled, FALSE otherwise
	 */
	public function isDisabled() {
		return $this->disabled;
	}

	/**
	 * This method is used to set the disable status of the task
	 *
	 * @param boolean $flag TRUE if task should be disabled, FALSE otherwise
	 * @return void
	 */
	public function setDisabled($flag) {
		if ($flag) {
			$this->disabled = TRUE;
		} else {
			$this->disabled = FALSE;
		}
	}

	/**
	 * This method is used to set the timestamp corresponding to the next execution time of the task
	 *
	 * @param integer $timestamp Timestamp of next execution
	 * @return void
	 */
	public function setExecutionTime($timestamp) {
		$this->executionTime = intval($timestamp);
	}

	/**
	 * This method returns the timestamp corresponding to the next execution time of the task
	 *
	 * @return integer Timestamp of next execution
	 */
	public function getExecutionTime() {
		return $this->executionTime;
	}

	/**
	 * Sets the internal reference to the singleton instance of the Scheduler
	 *
	 * @return void
	 */
	public function setScheduler() {
		$this->scheduler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Scheduler');
	}

	/**
	 * Unsets the internal reference to the singleton instance of the Scheduler
	 * This is done before a task is serialized, so that the scheduler instance
	 * is not saved to the database too
	 *
	 * @return void
	 */
	public function unsetScheduler() {
		unset($this->scheduler);
	}

	/**
	 * Registers a single execution of the task
	 *
	 * @param integer $timestamp Timestamp of the next execution
	 */
	public function registerSingleExecution($timestamp) {
		/** @var $execution \TYPO3\CMS\Scheduler\Execution */
		$execution = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Execution');
		$execution->setStart($timestamp);
		$execution->setInterval(0);
		$execution->setEnd($timestamp);
		$execution->setCronCmd('');
		$execution->setMultiple(0);
		$execution->setIsNewSingleExecution(TRUE);
		// Replace existing execution object
		$this->execution = $execution;
	}

	/**
	 * Registers a recurring execution of the task
	 *
	 * @param integer $start The first date/time where this execution should occur (timestamp)
	 * @param string $interval Execution interval in seconds
	 * @param integer $end The last date/time where this execution should occur (timestamp)
	 * @param boolean $multiple Set to FALSE if multiple executions of this task are not permitted in parallel
	 * @param string $cron_cmd Used like in crontab (minute hour day month weekday)
	 * @return void
	 */
	public function registerRecurringExecution($start, $interval, $end = 0, $multiple = FALSE, $cron_cmd = '') {
		/** @var $execution \TYPO3\CMS\Scheduler\Execution */
		$execution = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Execution');
		// Set general values
		$execution->setStart($start);
		$execution->setEnd($end);
		$execution->setMultiple($multiple);
		if (empty($cron_cmd)) {
			// Use interval
			$execution->setInterval($interval);
			$execution->setCronCmd('');
		} else {
			// Use cron syntax
			$execution->setInterval(0);
			$execution->setCronCmd($cron_cmd);
		}
		// Replace existing execution object
		$this->execution = $execution;
	}

	/**
	 * Sets the internal execution object
	 *
	 * @param \TYPO3\CMS\Scheduler\Execution $execution The execution to add
	 */
	public function setExecution(\TYPO3\CMS\Scheduler\Execution $execution) {
		$this->execution = $execution;
	}

	/**
	 * Returns the execution object
	 *
	 * @return \TYPO3\CMS\Scheduler\Execution The internal execution object
	 */
	public function getExecution() {
		return $this->execution;
	}

	/**
	 * Returns the timestamp for next due execution of the task
	 *
	 * @return integer Date and time of the next execution as a timestamp
	 */
	public function getNextDueExecution() {
		// NOTE: this call may throw an exception, but we let it bubble up
		return $this->execution->getNextExecution();
	}

	/**
	 * Returns TRUE if several runs of the task are allowed concurrently
	 *
	 * @return boolean TRUE if concurrent executions are allowed, FALSE otherwise
	 */
	public function areMultipleExecutionsAllowed() {
		return $this->execution->getMultiple();
	}

	/**
	 * Returns TRUE if an instance of the task is already running
	 *
	 * @return boolean TRUE if an instance is already running, FALSE otherwise
	 */
	public function isExecutionRunning() {
		$isRunning = FALSE;
		$queryArr = array(
			'SELECT' => 'serialized_executions',
			'FROM' => 'tx_scheduler_task',
			'WHERE' => 'uid = ' . intval($this->taskUid),
			'LIMIT' => 1
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArr);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (strlen($row['serialized_executions']) > 0) {
				$isRunning = TRUE;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $isRunning;
	}

	/**
	 * This method adds current execution to the execution list
	 * It also logs the execution time and mode
	 *
	 * @return integer Execution id
	 */
	public function markExecution() {
		$queryArr = array(
			'SELECT' => 'serialized_executions',
			'FROM' => 'tx_scheduler_task',
			'WHERE' => 'uid = ' . intval($this->taskUid),
			'LIMIT' => 1
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArr);
		$runningExecutions = array();
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (strlen($row['serialized_executions']) > 0) {
				$runningExecutions = unserialize($row['serialized_executions']);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		// Count the number of existing executions and use that number as a key
		// (we need to know that number, because it is returned at the end of the method)
		$numExecutions = count($runningExecutions);
		$runningExecutions[$numExecutions] = time();
		// Define the context in which the script is running
		$context = 'BE';
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
			$context = 'CLI';
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . intval($this->taskUid), array(
			'serialized_executions' => serialize($runningExecutions),
			'lastexecution_time' => time(),
			'lastexecution_context' => $context
		));
		return $numExecutions;
	}

	/**
	 * Removes given execution from list
	 *
	 * @param integer $executionID Id of the execution to remove.
	 * @param \Exception $failure An exception to signal a failed execution
	 * @return 	void
	 */
	public function unmarkExecution($executionID, \Exception $failure = NULL) {
		// Get the executions for the task
		$queryArr = array(
			'SELECT' => 'serialized_executions',
			'FROM' => 'tx_scheduler_task',
			'WHERE' => 'uid = ' . intval($this->taskUid),
			'LIMIT' => 1
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArr);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (strlen($row['serialized_executions']) > 0) {
				$runningExecutions = unserialize($row['serialized_executions']);
				// Remove the selected execution
				unset($runningExecutions[$executionID]);
				if (count($runningExecutions) > 0) {
					// Re-serialize the updated executions list (if necessary)
					$runningExecutionsSerialized = serialize($runningExecutions);
				} else {
					$runningExecutionsSerialized = '';
				}
				if ($failure instanceof \Exception) {
					// Log failed execution
					$logMessage = 'Task failed to execute successfully. Class: ' . get_class($this) . ', UID: ' . $this->taskUid . '. ' . $failure->getMessage();
					$this->scheduler->log($logMessage, 1, $failure->getCode());
					$failure = serialize($failure);
				} else {
					$failure = '';
				}
				// Save the updated executions list
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . intval($this->taskUid), array(
					'serialized_executions' => $runningExecutionsSerialized,
					'lastexecution_failure' => $failure
				));
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Clears all marked executions
	 *
	 * @return boolean TRUE if the clearing succeeded, FALSE otherwise
	 */
	public function unmarkAllExecutions() {
		// Set the serialized executions field to empty
		$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' . intval($this->taskUid), array(
			'serialized_executions' => ''
		));
		return $result;
	}

	/**
	 * Saves the details of the task to the database.
	 *
	 * @return bool
	 */
	public function save() {
		return $this->scheduler->saveTask($this);
	}

	/**
	 * Stops the task, by replacing the execution object by an empty one
	 * NOTE: the task still needs to be saved after that
	 *
	 * @return 	void
	 */
	public function stop() {
		$this->execution = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Execution');
	}

	/**
	 * Removes the task totally from the system.
	 *
	 * @return 	void
	 */
	public function remove() {
		$this->scheduler->removeTask($this);
	}

}


?>