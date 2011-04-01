<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * An abstract exception handler
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_error
 */
abstract class t3lib_error_AbstractExceptionHandler implements t3lib_error_ExceptionHandlerInterface, t3lib_Singleton {
	const CONTEXT_WEB = 'WEB';
	const CONTEXT_CLI = 'CLI';

	/**
	 * Displays the given exception
	 *
	 * @param Exception $exception The exception object
	 * @return void
	 */
	public function handleException(Exception $exception) {
		switch (PHP_SAPI) {
			case 'cli' :
				$this->echoExceptionCLI($exception);
				break;
			default :
				$this->echoExceptionWeb($exception);
		}
	}


	/**
	 * Writes exception to different logs
	 *
	 * @param Exception $exception The exception
	 * @param string	 the context where the exception was thrown, WEB or CLI
	 * @return void
	 * @see t3lib_div::sysLog(), t3lib_div::devLog()
	 */
	protected function writeLogEntries(Exception $exception, $context) {
		$filePathAndName = $exception->getFile();
		$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
		$logTitle = 'Core: Exception handler (' . $context . ')';
		$logMessage = 'Uncaught TYPO3 Exception: ' . $exceptionCodeNumber . $exception->getMessage() . ' | ' .
					  get_class($exception) . ' thrown in file ' . $filePathAndName . ' in line ' . $exception->getLine();
		$backtrace = $exception->getTrace();

			// write error message to the configured syslogs
		t3lib_div::sysLog($logMessage, $logTitle, 4);

			// When database credentials are wrong, the exception is probably
			// caused by this. Therefor we cannot do any database operation,
			// otherwise this will lead into recurring exceptions.
		try {
				// In case an error occurs before a database connection exists, try
				// to connect to the DB to be able to write the devlog/sys_log entry
			if (isset($GLOBALS['TYPO3_DB']) && is_object($GLOBALS['TYPO3_DB']) && empty($GLOBALS['TYPO3_DB']->link)) {
				$GLOBALS['TYPO3_DB']->connectDB();
			}

				// write error message to devlog
				// see: $TYPO3_CONF_VARS['SYS']['enable_exceptionDLOG']
			if (TYPO3_EXCEPTION_DLOG) {
				t3lib_div::devLog(
					$logMessage,
					$logTitle,
					3,
					array(
						'TYPO3_MODE' => TYPO3_MODE,
						'backtrace' => $backtrace
					)
				);
			}

				// write error message to sys_log table
			$this->writeLog($logTitle . ': ' . $logMessage);
		} catch (Exception $exception) {
			// Nothing happens here. It seems the database credentials are wrong
		}
	}

	/**
	 * Writes an exception in the sys_log table
	 *
	 * @param	string		Default text that follows the message.
	 * @return	void
	 */
	protected function writeLog($logMessage) {
		if (is_object($GLOBALS['TYPO3_DB']) && !empty($GLOBALS['TYPO3_DB']->link)) {
			$userId = 0;
			$workspace = 0;
			if (is_object($GLOBALS['BE_USER'])) {
				if (isset($GLOBALS['BE_USER']->user['uid'])) {
					$userId = $GLOBALS['BE_USER']->user['uid'];
				}
				if (isset($GLOBALS['BE_USER']->workspace)) {
					$workspace = $GLOBALS['BE_USER']->workspace;
				}
			}

			$fields_values = Array(
				'userid' => $userId,
				'type' => 5,
				'action' => 0,
				'error' => 2,
				'details_nr' => 0,
				'details' => $logMessage,
				'IP' => t3lib_div::getIndpEnv('REMOTE_ADDR'),
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'workspace' => $workspace
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_log', $fields_values);
		}
	}

	protected function sendStatusHeader(Exception $exception) {
		if (!headers_sent() && !($exception instanceof t3lib_error_http_StatusException)) {
				header(t3lib_utility_Http::HTTP_STATUS_500);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_abstractexceptionhandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_abstractexceptionhandler.php']);
}

?>