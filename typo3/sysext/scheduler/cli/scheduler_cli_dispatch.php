<?php
/**
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
/**
 * Starts all due tasks, used by the command line interface
 * This script must be included by the "CLI module dispatcher"
 *
 * @author 		Markus Friedrich <markus.friedrich@dkd.de>
 */
if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI && basename(PATH_thisScript) == 'cli_dispatch.phpsh') {
	$hasTask = TRUE;
	// Create an instance of the scheduler object
	/** @var $scheduler \TYPO3\CMS\Scheduler\Scheduler */
	$scheduler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Scheduler');
	/** @var \TYPO3\CMS\Core\Controller\CommandLineController $cli */
	$cli = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Controller\\CommandLineController');
	// If a specific id is given in arguments, then run that task. Otherwise run scheduled tasks.
	if ($cli->cli_isArg('-i')) {
		$taskId = (int)$cli->cli_argValue('-i');
		if ($taskId > 0) {
			// Force the execution of the task even if it is disabled or no execution scheduled
			if ($cli->cli_isArg('-f')) {
				$task = $scheduler->fetchTask($taskId);
			} else {
				$whereClause = 'uid = ' . $taskId . ' AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'];
				list($task) = $scheduler->fetchTasksWithCondition($whereClause);
			}
			if ($scheduler->isValidTaskObject($task)) {
				try {
					$scheduler->executeTask($task);
				} catch (\Exception $e) {

				}
				// Record the run in the system registry
				$scheduler->recordLastRun('cli-by-id');
			}
		}
	} else {
		// Loop as long as there are tasks
		do {
			// Try getting the next task and execute it
			// If there are no more tasks to execute, an exception is thrown by \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
			try {
				/** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
				$task = $scheduler->fetchTask();
				$hasTask = TRUE;
				try {
					$scheduler->executeTask($task);
				} catch (\Exception $e) {
					// We ignore any exception that may have been thrown during execution,
					// as this is a background process.
					// The exception message has been recorded to the database anyway
					continue;
				}
			} catch (\OutOfBoundsException $e) {
				$hasTask = FALSE;
			} catch (\UnexpectedValueException $e) {
				continue;
			}
		} while ($hasTask);
		// Record the run in the system registry
		$scheduler->recordLastRun();
	}
} else {
	die('This script must be included by the "CLI module dispatcher"');
}
