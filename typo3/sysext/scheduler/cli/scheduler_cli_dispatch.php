<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Markus Friedrich (markus.friedrich@dkd.de>
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
 * Starts all due tasks, used by the command line interface
 * This script must be included by the "CLI module dispatcher"
 *
 * @author		Markus Friedrich <markus.friedrich@dkd.de>
 * @package		TYPO3
 * @subpackage	tx_scheduler
 */
if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) && basename(PATH_thisScript) == 'cli_dispatch.phpsh') {
	$hasTask = TRUE;
		// Create an instance of the scheduler object
		/**
		 * @var	tx_scheduler
		 */
	$scheduler = t3lib_div::makeInstance('tx_scheduler');
		// Loop as long as there are tasks
	do {
			// Try getting the next task and execute it
			// If there are no more tasks to execute, an exception is thrown by tx_scheduler::fetchTask()
		try {
				/**
				 * @var	tx_scheduler_Task
				 */
			$task = $scheduler->fetchTask();
			$hasTask = TRUE;
			try {
				$scheduler->executeTask($task);
			}
			catch (Exception $e) {
					// We ignore any exception that may have been thrown during execution,
					// as this is a background process.
					// The exception message has been recorded to the database anyway
				continue;
			}
		}
			// There are no more tasks, quit the run
		catch (OutOfBoundsException $e) {
			$hasTask = FALSE;
		}
			// A task could not be unserialized properly, skip to next task
		catch (UnexpectedValueException $e) {
			continue;
		}
	} while ($hasTask);
		// Record the run in the system registry
	$scheduler->recordLastRun();
} else {
	die('This script must be included by the "CLI module dispatcher"');
}

?>