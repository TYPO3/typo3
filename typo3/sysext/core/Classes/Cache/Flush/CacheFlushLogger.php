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

namespace TYPO3\CMS\Core\Cache\Flush;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SysLog\Action\Cache as SystemLogCacheAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Repository\LogEntryRepository;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;

/**
 * Writes the sys_log entries of cache commands a backend user triggered.
 *
 * @internal only to be used by TYPO3 Core
 */
readonly class CacheFlushLogger
{
    public function __construct(
        private LogEntryRepository $logEntryRepository,
    ) {}

    /**
     * @param list<string> $commands
     */
    public function logCommands(BackendUserAuthentication $backendUser, array $commands): void
    {
        foreach ($commands as $command) {
            $this->logEntryRepository->writeLogEntryForBackendUser(
                $backendUser,
                SystemLogType::CACHE,
                SystemLogCacheAction::CLEAR,
                SystemLogErrorClassification::MESSAGE,
                'User {username} has cleared the cache (cacheCmd={command})',
                ['username' => $backendUser->user['username'] ?? '', 'command' => $command]
            );
        }
    }
}
