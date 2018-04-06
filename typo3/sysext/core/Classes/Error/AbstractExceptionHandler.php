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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * An abstract exception handler
 *
 * This file is a backport from TYPO3 Flow
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface, SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const CONTEXT_WEB = 'WEB';
    const CONTEXT_CLI = 'CLI';

    /**
     * Displays the given exception
     *
     * @param \Throwable $exception The throwable object.
     *
     * @throws \Exception
     */
    public function handleException(\Throwable $exception)
    {
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
     * @param \Throwable $exception The throwable object.
     * @param string $context The context where the exception was thrown, WEB or CLI
     */
    protected function writeLogEntries(\Throwable $exception, $context)
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
            $logMessage .= '. Requested URL: ' . $this->anonymizeToken(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        }
        // When database credentials are wrong, the exception is probably
        // caused by this. Therefor we cannot do any database operation,
        // otherwise this will lead into recurring exceptions.
        try {
            if ($this->logger) {
                $this->logger->critical($logTitle . ': ' . $logMessage, [
                    'TYPO3_MODE' => TYPO3_MODE,
                    'exception' => $exception
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
     */
    protected function writeLog($logMessage)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_log');

        if (!$connection->isConnected()) {
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

        $connection->insert(
            'sys_log',
            [
                'userid' => $userId,
                'type' => 5,
                'action' => 0,
                'error' => 2,
                'details_nr' => 0,
                'details' => str_replace('%', '%%', $logMessage),
                'log_data' => empty($data) ? '' : serialize($data),
                'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'workspace' => $workspace
            ]
        );
    }

    /**
     * Sends the HTTP Status 500 code, if $exception is *not* a
     * TYPO3\CMS\Core\Error\Http\StatusException and headers are not sent, yet.
     *
     * @param \Throwable $exception The throwable object.
     */
    protected function sendStatusHeaders(\Throwable $exception)
    {
        if (method_exists($exception, 'getStatusHeaders')) {
            $headers = $exception->getStatusHeaders();
        } else {
            $headers = [HttpUtility::HTTP_STATUS_500];
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
     * Replaces the generated token with a generic equivalent
     *
     * @param string $requestedUrl
     * @return string
     */
    protected function anonymizeToken(string $requestedUrl): string
    {
        $pattern = '/(?<=[tT]oken=)[0-9a-fA-F]{40}/';
        return preg_replace($pattern, '--AnonymizedToken--', $requestedUrl);
    }
}
