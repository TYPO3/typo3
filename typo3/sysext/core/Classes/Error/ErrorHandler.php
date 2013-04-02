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
 * Global error handler for TYPO3
 *
 * This file is a backport from FLOW3
 *
 * @author Rupert Germann <rupi@gmx.li>
 */
class ErrorHandler implements \TYPO3\CMS\Core\Error\ErrorHandlerInterface {

	/**
	 * Error levels which should result in an exception thrown.
	 *
	 * @var array
	 */
	protected $exceptionalErrors = array();

	/**
	 * Registers this class as default error handler
	 *
	 * @param integer $errorHandlerErrors The integer representing the E_* error level which should be
	 * @return void
	 */
	public function __construct($errorHandlerErrors) {
			// reduces error types to those a custom error handler can process
		$errorHandlerErrors = $errorHandlerErrors & ~(E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR);
		set_error_handler(array($this, 'handleError'), $errorHandlerErrors);
	}

	/**
	 * Defines which error levels should result in an exception thrown.
	 *
	 * @param integer $exceptionalErrors The integer representing the E_* error level to handle as exceptions
	 * @return void
	 */
	public function setExceptionalErrors($exceptionalErrors) {
		$this->exceptionalErrors = (int) $exceptionalErrors;
	}

	/**
	 * Handles an error.
	 * If the error is registered as exceptionalError it will by converted into an exception, to be handled
	 * by the configured exceptionhandler. Additionall the error message is written to the configured logs.
	 * If TYPO3_MODE is 'BE' the error message is also added to the flashMessageQueue, in FE the error message
	 * is displayed in the admin panel (as TsLog message)
	 *
	 * @param integer $errorLevel The error level - one of the E_* constants
	 * @param string $errorMessage The error message
	 * @param string $errorFile Name of the file the error occurred in
	 * @param integer $errorLine Line number where the error occurred
	 * @return void
	 * @throws \TYPO3\CMS\Core\Error\Exception with the data passed to this method if the error is registered as exceptionalError
	 */
	public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine) {
		// Don't do anything if error_reporting is disabled by an @ sign
		if (error_reporting() == 0) {
			return TRUE;
		}
		$errorLevels = array(
			E_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Runtime Notice',
			E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
			E_DEPRECATED => 'Runtime Deprecation Notice'
		);
		$message = 'PHP ' . $errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine;
		if ($errorLevel & $this->exceptionalErrors) {
				// handle error raised at early parse time
				// autoloader not available & built-in classes not resolvable
			if (!class_exists('stdClass', FALSE)) {
				$message = 'PHP ' . $errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . basename($errorFile) .
					'line ' . $errorLine;
				die($message);
			}
			// We need to manually require the exception classes in case the autoloader is not available at this point yet.
			// @see http://forge.typo3.org/issues/23444
			if (!class_exists('TYPO3\\CMS\\Core\\Error\\Exception', FALSE)) {
				require_once PATH_site . 'typo3/sysext/core/Classes/Exception.php';
				require_once PATH_site . 'typo3/sysext/core/Classes/Error/Exception.php';
			}
			throw new \TYPO3\CMS\Core\Error\Exception($message, 1);
		} else {
			switch ($errorLevel) {
			case E_USER_ERROR:

			case E_RECOVERABLE_ERROR:
				$severity = 2;
				break;
			case E_USER_WARNING:

			case E_WARNING:
				$severity = 1;
				break;
			default:
				$severity = 0;
				break;
			}
			$logTitle = 'Core: Error handler (' . TYPO3_MODE . ')';
			// Write error message to the configured syslogs,
			// see: $TYPO3_CONF_VARS['SYS']['systemLog']
			if ($errorLevel & $GLOBALS['TYPO3_CONF_VARS']['SYS']['syslogErrorReporting']) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, $logTitle, $severity);
			}
			// Write error message to devlog extension(s),
			// see: $TYPO3_CONF_VARS['SYS']['enable_errorDLOG']
			if (TYPO3_ERROR_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, $logTitle, $severity + 1);
			}
			// Write error message to TSlog (admin panel)
			if (is_object($GLOBALS['TT'])) {
				$GLOBALS['TT']->setTSlogMessage($logTitle . ': ' . $message, $severity + 1);
			}
			// Write error message to sys_log table (ext: belog, Tools->Log)
			if ($errorLevel & $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting']) {
				// Silently catch in case an error occurs before a database connection exists,
				// but DatabaseConnection fails to connect.
				try {
					$this->writeLog($logTitle . ': ' . $message, $severity);
				} catch (\Exception $e) {
				}
			}
			// Add error message to the flashmessageQueue
			if (defined('TYPO3_ERRORHANDLER_MODE') && TYPO3_ERRORHANDLER_MODE == 'debug') {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, 'PHP ' . $errorLevels[$errorLevel], $severity);
				/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
				$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
				/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
				$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
				$defaultFlashMessageQueue->enqueue($flashMessage);
			}
		}
		// Don't execute PHP internal error handler
		return TRUE;
	}

	/**
	 * Writes an error in the sys_log table
	 *
	 * @param string $logMessage Default text that follows the message (in english!).
	 * @param integer $severity The eror level of the message (0 = OK, 1 = warning, 2 = error)
	 * @return void
	 */
	protected function writeLog($logMessage, $severity) {
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
				'error' => $severity,
				'details_nr' => 0,
				'details' => $logMessage,
				'IP' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'workspace' => $workspace
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_log', $fields_values);
		}
	}

}


?>