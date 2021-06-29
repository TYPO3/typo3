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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SysLogChannel implements UpgradeWizardInterface
{
    protected Connection $sysLogTable;

    public function __construct()
    {
        $this->sysLogTable = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_log');
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'sysLogChannel';
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Populates a new channel column of the sys_log table.';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return <<<END
The logging system is migrating toward string-based channels rather than int-based types. This update populates the new column of existing log entries.
END;
    }

    /**
     * @inheritDoc
     */
    public function executeUpdate(): bool
    {
        $statement = $this->sysLogTable->prepare('UPDATE sys_log SET channel = ? WHERE type = ?');
        foreach (Type::channelMap() as $type => $channel) {
            $statement->executeQuery([$channel, $type]);
        }

        $statement = $this->sysLogTable->prepare('UPDATE sys_log SET level = ? WHERE type = ?');
        foreach (Type::levelMap() as $type => $level) {
            $statement->executeQuery([$level, $type]);
        }

        return true;
    }

    /**
     * If all log entries have a default channel, assume we've not mapped anything yet.
     */
    public function updateNecessary(): bool
    {
        $result = $this->sysLogTable->executeQuery('SELECT count(channel) FROM sys_log WHERE NOT channel="default"');
        return !$result->fetchOne();
    }

    /**
     * @inheritDoc
     */
    public function getPrerequisites(): array
    {
        // we need to make sure the new DB column was already added.
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }
}
