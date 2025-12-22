<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\SysLog\Repository;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Core\Security\PermissionSet\ProcessingContext;
use TYPO3\CMS\Core\SysLog\Type;

/**
 * Repository for writing system log entries to the database.
 *
 * Provides methods to create log entries in the sys_log table,
 * tracking user actions, errors, and system events.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class LogEntryRepository
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * Legacy wrapper for `writeLogEntry`, invoked with the current backend user.
     *
     * This method extracts the principal and processing context from the BackendUserAuthentication
     * object and delegates to writeLogEntry(). It automatically handles user impersonation
     * (switch user mode) by including the original user ID in the log data.
     *
     * @internal might be removed during TYPO3 v14 development
     */
    public function writeLogEntryForBackendUser(
        BackendUserAuthentication $backendUser,
        int $type,
        int $action,
        int $error,
        string $details,
        array $data,
        string $tableName = '',
        int|string $recordUid = '',
        int $eventPid = -1,
    ): int {
        if ($impersonatedBy = $backendUser->getOriginalUserIdWhenInSwitchUserMode()) {
            $impersonatedBy = new PrincipalContext(
                // @todo role is hardcoded here, but should be resolved
                role: PrincipalRole::ADMIN,
                id: $impersonatedBy,
            );
        } else {
            $impersonatedBy = null;
        }

        $principalContext = new PrincipalContext(
            role: $backendUser->getRole(),
            id: $backendUser->getUserId() ?? 0,
            impersonatedBy: $impersonatedBy,
        );
        $processingContext = new ProcessingContext(
            workspaceId: $backendUser->workspace,
        );

        return $this->writeLogEntry(
            $principalContext,
            $processingContext,
            $type,
            $action,
            $error,
            $details,
            $data,
            $tableName,
            $recordUid,
            $eventPid
        );
    }

    /**
     * Writes an entry to the system log (sys_log table).
     *
     * This method creates a log entry tracking user actions, errors, and system events.
     * The log entry includes context about the principal (user), workspace, and optionally
     * the database record affected by the action.
     *
     * @param PrincipalContext $principalContext The user/principal context that triggered the log entry, including user ID and role
     * @param ProcessingContext $processingContext The processing context, including workspace ID where the action occurred
     * @param int $type Type of action that created the log entry. Common types include:
     *                  1 (DB) for database operations, 2 (FILE) for file operations,
     *                  3 (CACHE) for cache operations, 4 (EXTENSION) for extension actions,
     *                  5 (ERROR) for errors, 255 (LOGIN) for login/logout events.
     *                  See \TYPO3\CMS\Core\SysLog\Type constants.
     * @param int $action Specific action ID within the type category. The meaning depends on $type.
     *                    Use 0 when no sub-categorization applies.
     * @param int $error Severity level: 0 = informational message, 1 = warning (user problem),
     *                   2 = system error (should not happen), 3 = security notice (for admins)
     * @param string $details The log message text. May contain sprintf-style placeholders (%s, %d, etc.)
     *                        that will be substituted with values from $data array.
     * @param array $data Additional data for the log entry. If provided, the first 5 elements (keys 0-4)
     *                    will be used to substitute placeholders in $details via sprintf.
     *                    Special key 'originalUser' is set automatically when user impersonation is active.
     * @param string $tableName Database table name of the record affected by this action (used by DataHandler).
     * @param int|string $recordUid UID of the record affected by this action (used by DataHandler).
     * @param int $eventPid Page UID where the event occurred. Used to filter log entries by page.
     *                      Use -1 for global events not tied to a specific page.
     * @param ServerRequestInterface|null $request The request object that triggered the log entry.
     * @return int The UID of the created log entry.
     */
    public function writeLogEntry(
        PrincipalContext $principalContext,
        ProcessingContext $processingContext,
        int $type,
        int $action,
        int $error,
        string $details,
        array $data,
        string $tableName = '',
        int|string $recordUid = '',
        int $eventPid = -1,
        ?ServerRequestInterface $request = null
    ): int {
        $userId = $principalContext->id;
        $workspaceId = $processingContext->workspaceId ?? 0;
        if ($principalContext->impersonatedBy !== null) {
            $data['originalUser'] = $principalContext->impersonatedBy->id;
        }

        $request ??= $GLOBALS['TYPO3_REQUEST'] ?? null;
        $connection = $this->connectionPool->getConnectionForTable('sys_log');
        $connection->insert(
            'sys_log',
            [
                'userid' => $userId,
                'type' => $type,
                'channel' => Type::toChannel($type),
                'level' => Type::toLevel($type),
                'action' => $action,
                'error' => $error,
                'details' => $details,
                'log_data' => $data !== [] ? json_encode($data) : '',
                'tablename' => $tableName,
                'recuid' => is_int($recordUid) ? $recordUid : 0,
                'IP' => $request?->getAttribute('normalizedParams')?->getRemoteAddress() ?? '',
                'tstamp' => $GLOBALS['EXEC_TIME'] ?? time(),
                'event_pid' => $eventPid,
                'workspace' => $workspaceId,
            ],
            [
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_INT,
                Connection::PARAM_STR,
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_INT,
            ]
        );
        return (int)$connection->lastInsertId();
    }
}
