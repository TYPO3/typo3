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

namespace TYPO3\CMS\Core\Database\Schema\EventListener;

use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use TYPO3\CMS\Core\Database\Schema\TableDiff;

/**
 * Event listener to handle additional processing for table alterations.
 */
class SchemaAlterTableListener
{
    /**
     * Listener for alter table events. This intercepts the building
     * of ALTER TABLE statements and adds the required statements to
     * change the ENGINE type on MySQL platforms if necessary.
     *
     * @param \Doctrine\DBAL\Event\SchemaAlterTableEventArgs $event
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function onSchemaAlterTable(SchemaAlterTableEventArgs $event)
    {
        /** @var TableDiff $tableDiff */
        $tableDiff = $event->getTableDiff();

        // Original Doctrine TableDiff without table options, continue default processing
        if (!$tableDiff instanceof TableDiff) {
            return false;
        }

        // Table options are only supported on MySQL, continue default processing
        if (!$event->getPlatform() instanceof MySqlPlatform) {
            return false;
        }

        // No changes in table options, continue default processing
        if (count($tableDiff->getTableOptions()) === 0) {
            return false;
        }

        $quotedTableName = $tableDiff->getName($event->getPlatform())->getQuotedName($event->getPlatform());

        // Add an ALTER TABLE statement to change the table engine to the list of statements.
        if ($tableDiff->hasTableOption('engine')) {
            $statement = 'ALTER TABLE ' . $quotedTableName . ' ENGINE = ' . $tableDiff->getTableOption('engine');
            $event->addSql($statement);
        }

        // continue default processing for all other changes.
        return false;
    }
}
