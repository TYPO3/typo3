<?php
namespace TYPO3\CMS\Core\Error;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Global error handler for TYPO3
 *
 * This file is a backport from TYPO3 Flow
 */
class ErrorHandler implements ErrorHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Error levels which should result in an exception thrown.
     *
     * @var int
     */
    protected $exceptionalErrors = 0;

    /**
     * Whether to write a flash message in case of an error
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Registers this class as default error handler
     *
     * @param int $errorHandlerErrors The integer representing the E_* error level which should be
     */
    public function __construct($errorHandlerErrors)
    {
        $excludedErrors = E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR;
        // reduces error types to those a custom error handler can process
        $errorHandlerErrors = $errorHandlerErrors & ~$excludedErrors;
        set_error_handler([$this, 'handleError'], $errorHandlerErrors);
    }

    /**
     * Defines which error levels should result in an exception thrown.
     *
     * @param int $exceptionalErrors The integer representing the E_* error level to handle as exceptions
     */
    public function setExceptionalErrors($exceptionalErrors)
    {
        $exceptionalErrors = (int)$exceptionalErrors;
        // We always disallow E_USER_DEPRECATED to generate exceptions as this may cause
        // bad user experience specifically during upgrades.
        $this->exceptionalErrors = $exceptionalErrors & ~E_USER_DEPRECATED;
    }

    /**
     * @param bool $debugMode
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = (bool)$debugMode;
    }

    /**
     * Handles an error.
     * If the error is registered as exceptionalError it will by converted into an exception, to be handled
     * by the configured exceptionhandler. Additionally the error message is written to the configured logs.
     * If TYPO3_MODE is 'BE' the error message is also added to the flashMessageQueue, in FE the error message
     * is displayed in the admin panel (as TsLog message)
     *
     * @param int $errorLevel The error level - one of the E_* constants
     * @param string $errorMessage The error message
     * @param string $errorFile Name of the file the error occurred in
     * @param int $errorLine Line number where the error occurred
     * @return bool
     * @throws Exception with the data passed to this method if the error is registered as exceptionalError
     */
    public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine)
    {
        // Don't do anything if error_reporting is disabled by an @ sign
        if (error_reporting() === 0) {
            return true;
        }
        $errorLevels = [
            E_WARNING => 'PHP Warning',
            E_NOTICE => 'PHP Notice',
            E_USER_ERROR => 'PHP User Error',
            E_USER_WARNING => 'PHP User Warning',
            E_USER_NOTICE => 'PHP User Notice',
            E_STRICT => 'PHP Runtime Notice',
            E_RECOVERABLE_ERROR => 'PHP Catchable Fatal Error',
            E_USER_DEPRECATED => 'TYPO3 Deprecation Notice',
            E_DEPRECATED => 'PHP Runtime Deprecation Notice'
        ];
        $message = $errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine;
        if ($errorLevel & $this->exceptionalErrors) {
            throw new Exception($message, 1476107295);
        }
        switch ($errorLevel) {
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                // no $flashMessageSeverity, as there will be no flash message for errors
                $severity = 2;
                break;
            case E_USER_WARNING:
            case E_WARNING:
                $flashMessageSeverity = FlashMessage::WARNING;
                $severity = 1;
                break;
            default:
                $flashMessageSeverity = FlashMessage::NOTICE;
                $severity = 0;
        }
        $logTitle = 'Core: Error handler (' . TYPO3_MODE . ')';
        $message = $logTitle . ': ' . $message;

        if ($errorLevel === E_USER_DEPRECATED) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger('TYPO3.CMS.deprecations');
            $logger->notice($message);
            return true;
        }
        if ($this->logger) {
            $this->logger->log(LogLevel::NOTICE - $severity, $message);
        }

        // Write error message to TSlog (admin panel)
        $timeTracker = $this->getTimeTracker();
        if (is_object($timeTracker)) {
            $timeTracker->setTSlogMessage($message, $severity + 1);
        }
        // Write error message to sys_log table (ext: belog, Tools->Log)
        if ($errorLevel & $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting']) {
            // Silently catch in case an error occurs before a database connection exists.
            try {
                $this->writeLog($message, $severity);
            } catch (\Exception $e) {
            }
        }
        if ($severity === 2) {
            // Let the internal handler continue. This will stop the script
            return false;
        }
        if ($this->debugMode) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                        \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        $message,
                        $errorLevels[$errorLevel],
                        $flashMessageSeverity
                    );
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Writes an error in the sys_log table
     *
     * @param string $logMessage Default text that follows the message (in english!).
     * @param int $severity The error level of the message (0 = OK, 1 = warning, 2 = error)
     */
    protected function writeLog($logMessage, $severity)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_log');
        if ($connection->isConnected()) {
            $userId = 0;
            $workspace = 0;
            $data = [];
            $backendUser = $this->getBackendUser();
            if (is_object($backendUser)) {
                if (isset($backendUser->user['uid'])) {
                    $userId = $backendUser->user['uid'];
                }
                if (isset($backendUser->workspace)) {
                    $workspace = $backendUser->workspace;
                }
                if (!empty($backendUser->user['ses_backuserid'])) {
                    $data['originalUser'] = $backendUser->user['ses_backuserid'];
                }
            }

            $connection->insert(
                'sys_log',
                [
                    'userid' => $userId,
                    'type' => 5,
                    'action' => 0,
                    'error' => $severity,
                    'details_nr' => 0,
                    'details' => str_replace('%', '%%', $logMessage),
                    'log_data' => empty($data) ? '' : serialize($data),
                    'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'workspace' => $workspace
                ]
            );
        }
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
