<?php
namespace TYPO3\CMS\Core\Error;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * @author Ingo Renner <ingo@typo3.org>
 */
abstract class AbstractExceptionHandler implements \TYPO3\CMS\Core\Error\ExceptionHandlerInterface, \TYPO3\CMS\Core\SingletonInterface {

	const CONTEXT_WEB = 'WEB';
	const CONTEXT_CLI = 'CLI';
	/**
	 * Displays the given exception
	 *
	 * @param Exception $exception The exception object
	 * @return void
	 */
	public function handleException(\Exception $exception) {
		switch (PHP_SAPI) {
		case 'cli':
			$this->echoExceptionCLI($exception);
			break;
		default:
			$this->echoExceptionWeb($exception);
		}
	}

	/**
	 * Writes exception to different logs
	 *
	 * @param Exception $exception The exception
	 * @param string $context The context where the exception was thrown, WEB or CLI
	 * @return void
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(), \TYPO3\CMS\Core\Utility\GeneralUtility::devLog()
	 */
	protected function writeLogEntries(\Exception $exception, $context) {
		$filePathAndName = $exception->getFile();
		$exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
		$logTitle = 'Core: Exception handler (' . $context . ')';
		$logMessage = 'Uncaught TYPO3 Exception: ' . $exceptionCodeNumber . $exception->getMessage() . ' | ' . get_class($exception) . ' thrown in file ' . $filePathAndName . ' in line ' . $exception->getLine();
		if ($context === 'WEB') {
			$logMessage .= '. Requested URL: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
		}
		$backtrace = $exception->getTrace();
		// Write error message to the configured syslogs
		\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($logMessage, $logTitle, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL);
		// When database credentials are wrong, the exception is probably
		// caused by this. Therefor we cannot do any database operation,
		// otherwise this will lead into recurring exceptions.
		try {
			// Write error message to devlog
			// see: $TYPO3_CONF_VARS['SYS']['enable_exceptionDLOG']
			if (TYPO3_EXCEPTION_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($logMessage, $logTitle, 3, array(
					'TYPO3_MODE' => TYPO3_MODE,
					'backtrace' => $backtrace
				));
			}
			// Write error message to sys_log table
			$this->writeLog($logTitle . ': ' . $logMessage);
		} catch (\Exception $exception) {

		}
	}

	/**
	 * Writes an exception in the sys_log table
	 *
	 * @param string $logMessage Default text that follows the message.
	 * @return void
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
			$fields_values = array(
				'userid' => $userId,
				'type' => 5,
				'action' => 0,
				'error' => 2,
				'details_nr' => 0,
				'details' => str_replace('%', '%%', $logMessage),
				'IP' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'workspace' => $workspace
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_log', $fields_values);
		}
	}

	/**
	 * Sends the HTTP Status 500 code, if $exception is *not* a
	 * TYPO3\\CMS\\Core\\Error\\Http\\StatusException and headers are not sent, yet.
	 *
	 * @param Exception $exception
	 * @return void
	 */
	protected function sendStatusHeaders(\Exception $exception) {
		if (method_exists($exception, 'getStatusHeaders')) {
			$headers = $exception->getStatusHeaders();
		} else {
			$headers = array(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_500);
		}
		if (!headers_sent()) {
			foreach ($headers as $header) {
				header($header);
			}
		}
	}

}


?>
