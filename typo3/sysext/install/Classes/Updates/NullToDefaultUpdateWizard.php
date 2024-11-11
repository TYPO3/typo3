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

use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * Update current "NULL" fields to their desired DEFAULT value
 * if new database schema (TCA) defines a "NOT NULL" assertion (nullable=false).
 *
 * @since 13.4
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('nullToDefaultUpdateWizard')]
class NullToDefaultUpdateWizard implements UpgradeWizardInterface, RepeatableInterface
{
    public function __construct(
        private readonly SchemaMigrator $schemaMigrator,
        private readonly ConnectionPool $connectionPool
    ) {}

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrate NULL field values to DEFAULT values';
    }

    /**
     * @return string Longer description of this updater
     * @throws \RuntimeException
     */
    public function getDescription(): string
    {
        $nullFields = $this->getNullFieldsThatNeedUpdate();
        $description = 'Fields containing NULL values that need to be updated to DEFAULT values:';
        foreach ($nullFields as $info) {
            $description .= LF . sprintf(
                '%s.%s (affects %d rows)',
                $info['tableDiff']->getOldTable()->getName(),
                $info['columnDiff']->getOldColumn()->getName(),
                $info['count'],
            );
        }
        return $description;
    }

    /**
     * @return list<array{columnDiff: ColumnDiff, tableDiff: TableDiff, count: int}>
     */
    protected function getNullFieldsThatNeedUpdate(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $databaseDefinitions = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $databaseDifferences = $this->schemaMigrator->getSchemaDiffs($databaseDefinitions);
        $updates = [];
        foreach ($databaseDifferences as $schemaDiff) {
            foreach ($schemaDiff->getAlteredTables() as $changedTable) {
                foreach ($changedTable->getChangedColumns() as $changedColumn) {
                    if (!$changedColumn->hasNotNullChanged()) {
                        continue;
                    }

                    $newColumn = $changedColumn->getNewColumn();
                    $isNewColumnMigratable = $newColumn->getNotNull() && $newColumn->getDefault() !== null;
                    if (!$isNewColumnMigratable) {
                        continue;
                    }

                    $tableName = $changedTable->getOldTable()->getName();
                    $fieldName = $changedColumn->getOldColumn()->getName();

                    $qb = $this->connectionPool->getQueryBuilderForTable($tableName);
                    $qb->getRestrictions()->removeAll();
                    $numberOfNullRows = (int)$qb
                        ->count('*')
                        ->from($tableName)
                        ->where($qb->expr()->isNull($fieldName))
                        ->executeQuery()
                        ->fetchOne();

                    if ($numberOfNullRows > 0) {
                        $updates[] = [
                            'columnDiff' => $changedColumn,
                            'tableDiff' => $changedTable,
                            'count' => $numberOfNullRows,
                        ];
                    }
                }
            }
        }

        return $updates;
    }

    public function updateNecessary(): bool
    {
        return $this->getNullFieldsThatNeedUpdate() !== [];
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function executeUpdate(): bool
    {
        foreach ($this->getNullFieldsThatNeedUpdate() as $info) {
            $columnDiff = $info['columnDiff'];
            $tableDiff = $info['tableDiff'];

            $tableName = $tableDiff->getOldTable()->getName();
            $fieldName = $columnDiff->getOldColumn()->getName();
            $defaultValue = $columnDiff->getNewColumn()->getDefault();
            $connection = $this->connectionPool->getConnectionForTable($tableName);

            $connection->update(
                $tableName,
                [$fieldName => $defaultValue],
                [$fieldName => null]
            );
        }
        return true;
    }
}
