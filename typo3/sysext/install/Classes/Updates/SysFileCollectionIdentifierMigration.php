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
#[UpgradeWizard('sysFileCollectionIdentifierMigration')]
class SysFileCollectionIdentifierMigration implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'sys_file_collection';

    public function getTitle(): string
    {
        return 'Migrate storage and folder to the new folder_identifier property of the "sys_file_collection" table.';
    }

    public function getDescription(): string
    {
        return 'The "sys_file_collection" table has a new identifier property which is used to identify the mount point. This update migrates the properties "storage" and "folder" to the new "folder_identifier" property.';
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
                ['folder_identifier' => $record['storage'] . ':' . $record['folder']],
                ['uid' => (int)$record['uid']]
            );
        }

        return true;
    }

    protected function columnsExistInTable(): bool
    {
        $schemaManager = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)->createSchemaManager();

        $tableColumns = $schemaManager->listTableColumns(self::TABLE_NAME);

        foreach (['storage', 'folder', 'folder_identifier'] as $column) {
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
        return $this->getPreparedQueryBuilder()->select('uid', 'storage', 'folder', 'folder_identifier')->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->gt('storage', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('folder', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('folder_identifier', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('folder'))
            );

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
