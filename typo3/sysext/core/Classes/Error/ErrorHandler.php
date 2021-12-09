<?php

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

namespace TYPO3\CMS\Core\Error;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SysLog\Action as SystemLogGenericAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
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
     */
    protected int $exceptionalErrors = 0;

    /**
     * Error levels which should be handled.
     */
    protected int $errorHandlerErrors = 0;

    /**
     * Whether to write a flash message in case of an error
     */
    protected bool $debugMode = false;

    protected const ERROR_LEVEL_LABELS = [
        E_WARNING => 'PHP Warning',
        E_NOTICE => 'PHP Notice',
        E_USER_ERROR => 'PHP User Error',
        E_USER_WARNING => 'PHP User Warning',
        E_USER_NOTICE => 'PHP User Notice',
        E_STRICT => 'PHP Runtime Notice',
        E_RECOVERABLE_ERROR => 'PHP Catchable Fatal Error',
        E_USER_DEPRECATED => 'TYPO3 Deprecation Notice',
        E_DEPRECATED => 'PHP Runtime Deprecation Notice',
    ];

    /**
     * Registers this class as default error handler
     *
     * @param int $errorHandlerErrors The integer representing the E_* error level which should be
     */
    public function __construct($errorHandlerErrors)
    {
        $excludedErrors = E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR;
        // reduces error types to those a custom error handler can process
        $this->errorHandlerErrors = (int)$errorHandlerErrors & ~$excludedErrors;
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

    public function registerErrorHandler()
    {
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Handles an error.
     * If the error is registered as exceptionalError it will by converted into an exception, to be handled
     * by the configured exceptionhandler. Additionally the error message is written to the configured logs.
     * If application is backend, the error message is also added to the flashMessageQueue, in frontend the
     * error message is displayed in the admin panel (as TsLog message).
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
        // Filter all errors, that should not be reported/ handled from current error reporting
        $reportingLevel = $this->errorHandlerErrors & error_reporting();
        // Since symfony does this:
        // @trigger_error('...', E_USER_DEPRECATED), and we DO want to log these,
        // we always enforce deprecation messages to be handled, even when they are silenced
        $reportingLevel |= E_USER_DEPRECATED;
        $shouldHandleError = (bool)($reportingLevel & $errorLevel);
        if (!$shouldHandleError) {
            return self::ERROR_HANDLED;
        }

        $message = self::ERROR_LEVEL_LABELS[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine;
        if ($errorLevel & $this->exceptionalErrors) {
            throw new Exception($message, 1476107295);
        }

        $message = $this->getFormattedLogMessage($message);

        if ($errorLevel === E_USER_DEPRECATED || $errorLevel === E_DEPRECATED) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger('TYPO3.CMS.deprecations');
            $logger->notice($message);
            return self::ERROR_HANDLED;
        }

        switch ($errorLevel) {
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $logLevel = LogLevel::ERROR;
                break;
            case E_USER_WARNING:
            case E_WARNING:
                $logLevel = LogLevel::WARNING;
                break;
            default:
                $logLevel = LogLevel::NOTICE;
        }

        if ($this->logger) {
            $this->logger->log($logLevel, $message);
        }

        try {
            // Write error message to TSlog (admin panel)
            $this->getTimeTracker()->setTSlogMessage($message, $logLevel);
        } catch (\Throwable $e) {
            // Silently catch in case an error occurs before the DI container is in place
        }
        // Write error message to sys_log table (ext: belog, Tools->Log)
        if ($errorLevel & ($GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting'] ?? 0)) {
            // Silently catch in case an error occurs before a database connection exists.
            try {
                $this->writeLog($message, $logLevel);
            } catch (\Exception $e) {
            }
        }
        if ($logLevel === LogLevel::ERROR) {
            // Let the internal handler continue. This will stop the script
            return self::PROPAGATE_ERROR;
        }
        if ($this->debugMode) {
            $this->createAndEnqueueFlashMessage($message, $errorLevel);
        }
        // Don't execute PHP internal error handler
        return self::ERROR_HANDLED;
    }

    protected function createAndEnqueueFlashMessage(string $message, int $errorLevel): void
    {
        switch ($errorLevel) {
            case E_USER_WARNING:
            case E_WARNING:
                $flashMessageSeverity = FlashMessage::WARNING;
                break;
            default:
                $flashMessageSeverity = FlashMessage::NOTICE;
        }
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            self::ERROR_LEVEL_LABELS[$errorLevel],
            $flashMessageSeverity
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Writes an error in the sys_log table
     *
     * @param string $logMessage Default text that follows the message (in english!).
     * @param string $logLevel The error level, see LogLevel::* constants
     */
    protected function writeLog($logMessage, string $logLevel)
    {
        // Avoid ConnectionPool usage prior boot completion, as that is deprecated since #94979.
        if (!GeneralUtility::getContainer()->get('boot.state')->complete) {
            if ($this->logger) {
                // Log via debug(), the original message has already been logged with the original serverity in handleError().
                // This log entry is targeted for users that try to debug why a log record is missing in sys_log
                // while it has been logged to the logging framework.
                $this->logger->debug(
                    'An error could not be logged to database as it appeared during early bootstrap (ext_tables.php or ext_localconf.php loading).',
                    ['original_message' => $logMessage, 'original_loglevel' => $logLevel]
                );
            }
            return;
        }
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
                if ($backUserId = $backendUser->getOriginalUserIdWhenInSwitchUserMode()) {
                    $data['originalUser'] = $backUserId;
                }
            }

            switch ($logLevel) {
                case LogLevel::ERROR:
                    $severity = 2;
                    break;
                case LogLevel::WARNING:
                    $severity = 1;
                    break;
                case LogLevel::NOTICE:
                default:
                    $severity = 0;
                    break;
            }

            $connection->insert(
                'sys_log',
                [
                    'userid' => $userId,
                    'type' => SystemLogType::ERROR,
                    'channel' => SystemLogType::toChannel(SystemLogType::ERROR),
                    'action' => SystemLogGenericAction::UNDEFINED,
                    'error' => SystemLogErrorClassification::SYSTEM_ERROR,
                    'level' => $severity,
                    'details_nr' => 0,
                    'details' => str_replace('%', '%%', $logMessage),
                    'log_data' => empty($data) ? '' : serialize($data),
                    'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'workspace' => $workspace,
                ]
            );
        }
    }

    protected function getFormattedLogMessage(string $message): string
    {
        // String 'FE' if in FrontendApplication, else 'BE' (also in CLI without request object)
        $applicationType = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
        && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() ? 'FE' : 'BE';
        $logPrefix = 'Core: Error handler (' . $applicationType . ')';
        return $logPrefix . ': ' . $message;
    }

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
