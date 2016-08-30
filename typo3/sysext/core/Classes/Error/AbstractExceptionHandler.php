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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract exception handler
 *
 * This file is a backport from TYPO3 Flow
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface, \TYPO3\CMS\Core\SingletonInterface
{
    const CONTEXT_WEB = 'WEB';
    const CONTEXT_CLI = 'CLI';

    /**
     * Displays the given exception
     *
     * @param \Exception|\Throwable $exception The exception(PHP 5.x) or throwable(PHP >= 7.0) object.
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     *
     * @throws \Exception
     */
    public function handleException($exception)
    {
        if ($exception instanceof \Throwable || $exception instanceof \Exception) {
            switch (PHP_SAPI) {
                case 'cli':
                    $this->echoExceptionCLI($exception);
                    break;
                default:
                    $this->echoExceptionWeb($exception);
            }
        } else {
            throw new \Exception('handleException was called the wrong way.', 1450714322);
        }
    }

    /**
     * Writes exception to different logs
     *
     * @param \Exception|\Throwable $exception The exception(PHP 5.x) or throwable(PHP >= 7.0) object.
     * @param string $context The context where the exception was thrown, WEB or CLI
     * @return void
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(), \TYPO3\CMS\Core\Utility\GeneralUtility::devLog()
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    protected function writeLogEntries($exception, $context)
    {
        // Do not write any logs for this message to avoid filling up tables or files with illegal requests
        if ($exception->getCode() === 1396795884) {
            return;
        }
        $filePathAndName = $exception->getFile();
        $exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
        $logTitle = 'Core: Exception handler (' . $context . ')';
        $logMessage = 'Uncaught TYPO3 Exception: ' . $exceptionCodeNumber . $exception->getMessage() . ' | '
            . get_class($exception) . ' thrown in file ' . $filePathAndName . ' in line ' . $exception->getLine();
        if ($context === 'WEB') {
            $logMessage .= '. Requested URL: ' . GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        }
        $backtrace = $exception->getTrace();
        // Write error message to the configured syslogs
        GeneralUtility::sysLog($logMessage, $logTitle, GeneralUtility::SYSLOG_SEVERITY_FATAL);
        // When database credentials are wrong, the exception is probably
        // caused by this. Therefor we cannot do any database operation,
        // otherwise this will lead into recurring exceptions.
        try {
            // Write error message to devlog
            // see: $TYPO3_CONF_VARS['SYS']['enable_exceptionDLOG']
            if (TYPO3_EXCEPTION_DLOG) {
                GeneralUtility::devLog($logMessage, $logTitle, 3, [
                    'TYPO3_MODE' => TYPO3_MODE,
                    'backtrace' => $backtrace
                ]);
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
    protected function writeLog($logMessage)
    {
        $databaseConnection = $this->getDatabaseConnection();
        if (!is_object($databaseConnection) || !$databaseConnection->isConnected()) {
            return;
        }
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
        $fields_values = [
            'userid' => $userId,
            'type' => 5,
            'action' => 0,
            'error' => 2,
            'details_nr' => 0,
            'details' => str_replace('%', '%%', $logMessage),
            'log_data' => (empty($data) ? '' : serialize($data)),
            'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'workspace' => $workspace
        ];
        $databaseConnection->exec_INSERTquery('sys_log', $fields_values);
    }

    /**
     * Sends the HTTP Status 500 code, if $exception is *not* a
     * TYPO3\CMS\Core\Error\Http\StatusException and headers are not sent, yet.
     *
     * @param \Exception|\Throwable $exception The exception(PHP 5.x) or throwable(PHP >= 7.0) object.
     * @return void
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    protected function sendStatusHeaders($exception)
    {
        if (method_exists($exception, 'getStatusHeaders')) {
            $headers = $exception->getStatusHeaders();
        } else {
            $headers = [\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_500];
        }
        if (!headers_sent()) {
            foreach ($headers as $header) {
                header($header);
            }
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Gets the Database Object
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
