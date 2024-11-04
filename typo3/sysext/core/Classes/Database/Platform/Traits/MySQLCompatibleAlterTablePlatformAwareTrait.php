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

namespace TYPO3\CMS\Core\Database\Platform\Traits;

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;
use TYPO3\CMS\Core\Database\Schema\TableDiff;

/**
 * `doctrine/dbal` does not support handling engine options directly. This trait in combination with extended
 * platform classes substitutes the deprecated `doctrine/event-manager` approach to influence database schema
 * related comparison and DDL handling.
 *
 * @internal shared code for extended MySQL and MariDB platform doctrine classes.
 */
trait MySQLCompatibleAlterTablePlatformAwareTrait
{
    /**
     * @param TableDiff|DoctrineTableDiff $tableDiff
     * @param list<string> $result
     * @return list<string>
     */
    protected function getCustomAlterTableSQLEngineOptions(DoctrineMariaDBPlatform|DoctrineMySQLPlatform $platform, TableDiff|DoctrineTableDiff $tableDiff, array $result): array
    {
        // Original Doctrine TableDiff without table options, continue default processing
        if (!$tableDiff instanceof TableDiff) {
            return $result;
        }

        // No changes in table options, continue default processing
        if (count($tableDiff->getTableOptions()) === 0) {
            return $result;
        }
        $options = '';

        if ($tableDiff->hasTableOption('engine')) {
            $options .= ' ENGINE = ' . $tableDiff->getTableOption('engine');
        }

        if ($tableDiff->hasTableOption('row_format')) {
            $options .= ' ROW_FORMAT = ' . $tableDiff->getTableOption('row_format');
        } elseif ($tableDiff->hasTableOption('engine') && $tableDiff->getOldTable()->hasOption('row_format')) {
            // Ensure ROW_FORMAT is always explicitly applied if ENGINE is changed,
            // as "old" CREATE TABLE ROW_FORMAT options are cached and are re-applied if ENGINE is changed
            // (which would result in a "Wrong create options" error if MyISAM/FIXED is tried to be changed to InnoDB(+implicit FIXED)
            //
            // See  https://bugs.mysql.com/bug.php?id=26214#c104034 into account:
            // > Row_format column in SHOW TABLE STATUS shows the actual row format of the table.
            // > Create_options in SHOW TABLE STATUS and SHOW CREATE TABLE show the options (including row format) that you specified at CREATE TABLE time.
            // >
            // > The original options are preserved because you may do ALTER TABLE ... ENGINE= and change the storage engine of the table, and a new storage
            // > engine may support the row format that you specified back then during CREATE TABLE.
            $options .= ' ROW_FORMAT = ' . $tableDiff->getOldTable()->getOption('row_format');
        }

        // Add an ALTER TABLE statement to change the table engine to the list of statements.
        if ($options !== '') {
            $quotedTableName = $tableDiff->getOldTable()->getQuotedName($platform);
            $result[] = 'ALTER TABLE ' . $quotedTableName . $options;
        }

        return $result;
    }
}
