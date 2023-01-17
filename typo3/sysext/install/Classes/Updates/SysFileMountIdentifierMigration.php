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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('sysFileMountIdentifierMigration')]
class SysFileMountIdentifierMigration implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'sys_filemounts';

    public function getTitle(): string
    {
        return 'Migrate base and path to the new identifier property of the "sys_filemounts" table.';
    }

    public function getDescription(): string
    {
        return 'The "sys_filemounts" table has a new identifier property which is used to identify the mount point. This update migrates the properties "base" and "path" to the new identifier property.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->columnsExistInTable() && $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);

        foreach ($this->getRecordsToUpdate() as $record) {
            $connection->update(
                self::TABLE_NAME,
                ['identifier' => $record['base'] . ':' . $record['path']],
                ['uid' => (int)$record['uid']]
            );
        }

        return true;
    }

    protected function columnsExistInTable(): bool
    {
        $schemaManager = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)->createSchemaManager();

        $tableColumns = $schemaManager->listTableColumns(self::TABLE_NAME);

        foreach (['path', 'base', 'identifier'] as $column) {
            if (!isset($tableColumns[$column])) {
                return false;
            }
        }

        return true;
    }

    protected function hasRecordsToUpdate(): bool
    {
        return (bool)$this->getPreparedQueryBuilder()->count('uid')->executeQuery()->fetchOne();
    }

    protected function getRecordsToUpdate(): array
    {
        return $this->getPreparedQueryBuilder()->select(...['uid', 'path', 'base'])->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->gt('base', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('path', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter(''))
            );

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
