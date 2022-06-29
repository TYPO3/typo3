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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\SysLog\Action as SystemLogGenericAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
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

    protected bool $logExceptionStackTrace = false;

    private const IGNORED_EXCEPTION_CODES = [
        1396795884, // Current host header value does not match the configured trusted hosts pattern
        1616175867, // Backend login request is rate limited
        1616175847, // Frontend login request is rate limited
    ];

    public const IGNORED_HMAC_EXCEPTION_CODES = [
        1581862822, // Failed HMAC validation due to modified __trustedProperties in extbase property mapping
        1581862823, // Failed HMAC validation due to modified form state in ext:forms
    ];

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
     * @param string $mode The context where the exception was thrown.
     *     Either self::CONTEXT_WEB or self::CONTEXT_CLI.
     */
    protected function writeLogEntries(\Throwable $exception, string $mode): void
    {
        // Do not write any logs for some messages to avoid filling up tables or files with illegal requests
        $ignoredCodes = array_merge(self::IGNORED_EXCEPTION_CODES, self::IGNORED_HMAC_EXCEPTION_CODES);
        if (in_array($exception->getCode(), $ignoredCodes, true)) {
            return;
        }

        // PSR-3 logging framework.
        try {
            if ($this->logger) {
                // 'FE' if in FrontendApplication, else 'BE' (also in CLI without request object)
                $applicationMode = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                    && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
                    ? 'FE'
                    : 'BE';
                $requestUrl = $this->anonymizeToken(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
                $this->logger->critical('Core: Exception handler ({mode}: {application_mode}): {exception_class}, code #{exception_code}, file {file}, line {line}: {message}', [
                    'mode' => $mode,
                    'application_mode' => $applicationMode,
                    'exception_class' => get_class($exception),
                    'exception_code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'message' => $exception->getMessage(),
                    'request_url' => $requestUrl,
                    'exception' => $this->logExceptionStackTrace ? $exception : null,
                ]);
            }
        } catch (\Exception $exception) {
            // A nested exception here was probably caused by a database failure, which means there's little
            // else that can be done other than moving on and letting the system hard-fail.
        }

        // Legacy logger.  Remove this section eventually.
        $filePathAndName = $exception->getFile();
        $exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
        $logTitle = 'Core: Exception handler (' . $mode . ')';
        $logMessage = 'Uncaught TYPO3 Exception: ' . $exceptionCodeNumber . $exception->getMessage() . ' | '
            . get_class($exception) . ' thrown in file ' . $filePathAndName . ' in line ' . $exception->getLine();
        if ($mode === self::CONTEXT_WEB) {
            $logMessage .= '. Requested URL: ' . $this->anonymizeToken(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        }
        // When database credentials are wrong, the exception is probably
        // caused by this. Therefore we cannot do any database operation,
        // otherwise this will lead into recurring exceptions.
        try {
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
    protected function writeLog(string $logMessage)
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
        if ($backendUser instanceof BackendUserAuthentication) {
            if (isset($backendUser->user['uid'])) {
                $userId = $backendUser->user['uid'];
            }
            $workspace = $backendUser->workspace;
            if ($backUserId = $backendUser->getOriginalUserIdWhenInSwitchUserMode()) {
                $data['originalUser'] = $backUserId;
            }
        }

        $connection->insert(
            'sys_log',
            [
                'userid' => $userId,
                'type' => SystemLogType::ERROR,
                'channel' => SystemLogType::toChannel(SystemLogType::ERROR),
                'action' => SystemLogGenericAction::UNDEFINED,
                'error' => SystemLogErrorClassification::SYSTEM_ERROR,
                'details_nr' => 0,
                'details' => str_replace('%', '%%', $logMessage),
                'log_data' => empty($data) ? '' : serialize($data),
                'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'workspace' => $workspace,
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
     * @return BackendUserAuthentication|null
     */
    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    /**
     * Replaces the generated token with a generic equivalent
     *
     * @param string $requestedUrl
     * @return string
     */
    protected function anonymizeToken(string $requestedUrl): string
    {
        $pattern = '/(?:(?<=[tT]oken=)|(?<=[tT]oken%3D))[0-9a-fA-F]{40}/';
        return preg_replace($pattern, '--AnonymizedToken--', $requestedUrl);
    }
}
