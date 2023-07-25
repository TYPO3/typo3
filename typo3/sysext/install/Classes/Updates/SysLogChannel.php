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

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\StringType;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('sysLogChannel')]
class SysLogChannel implements UpgradeWizardInterface
{
    protected Connection $sysLogTable;

    public function __construct()
    {
        $this->sysLogTable = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_log');
    }

    public function getTitle(): string
    {
        return 'Populates a new channel column of the sys_log table.';
    }

    public function getDescription(): string
    {
        return <<<END
The logging system is migrating toward string-based channels rather than int-based types. This update populates the new column of existing log entries.
END;
    }

    public function executeUpdate(): bool
    {
        $statement = $this->sysLogTable->prepare('UPDATE sys_log SET channel = ? WHERE type = ?');
        foreach (Type::channelMap() as $type => $channel) {
            $statement->bindValue(1, $channel);
            $statement->bindValue(2, $type, ParameterType::INTEGER);
            $statement->executeStatement();
        }

        // Ensure the level field is a varchar, otherwise we are in trouble when logging into TYPO3 Backend.
        $table = $this->sysLogTable->getSchemaInformation()->introspectTable('sys_log');
        if (!$table->getColumn('level')->getType() instanceof StringType) {
            $schema = $this->sysLogTable->createSchemaManager();
            $schema->alterTable(new TableDiff(
                'sys_log',
                [],
                [new ColumnDiff('level', new Column('level', new StringType(), ['length' => 10, 'default' => 'info', 'notnull' => true]))],
                [],
                [],
                [],
                [],
                $table
            ));
            Bootstrap::createCache('database_schema')->flush();
        }

        $statement = $this->sysLogTable->prepare('UPDATE sys_log SET level = ? WHERE type = ?');
        foreach (Type::levelMap() as $type => $level) {
            $statement->bindValue(1, $level);
            $statement->bindValue(2, $type, ParameterType::INTEGER);
            $statement->executeStatement();
        }

        return true;
    }

    /**
     * If all log entries have a default channel, assume we've not mapped anything yet.
     */
    public function updateNecessary(): bool
    {
        try {
            $result = $this->sysLogTable->executeQuery('SELECT count(channel) FROM sys_log WHERE NOT channel="default"');
            return !$result->fetchOne();
        } catch (\Doctrine\DBAL\Exception\InvalidFieldNameException $e) {
            return true;
        }
    }

    public function getPrerequisites(): array
    {
        // we need to make sure the new DB column was already added.
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
