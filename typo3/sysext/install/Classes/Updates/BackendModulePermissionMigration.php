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

use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('backendModulePermission')]
class BackendModulePermissionMigration implements UpgradeWizardInterface
{
    protected array $aliases = [];

    public function __construct()
    {
        $this->aliases = GeneralUtility::makeInstance(ModuleRegistry::class)->getModuleAliases();
    }

    public function getTitle(): string
    {
        return 'Migrate backend user and groups to new module names.';
    }

    public function getDescription(): string
    {
        return 'Update backend user and groups to migrate to possible new module names.';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        return $this->aliases !== []
            && (
                $this->hasRecordsToUpdate('be_groups', 'groupMods')
                || $this->hasRecordsToUpdate('be_users', 'userMods')
            );
    }

    public function executeUpdate(): bool
    {
        $this->updateRecords('be_groups', 'groupMods');
        $this->updateRecords('be_users', 'userMods');
        return true;
    }

    protected function hasRecordsToUpdate(string $table, string $field): bool
    {
        $queryBuilder = $this->getPreparedQueryBuilder($table, $field);
        $statement = $queryBuilder->select($field)->executeQuery();
        $aliasIdentifiers = array_keys($this->aliases);
        while ($record = $statement->fetchAssociative()) {
            $selectedModules = GeneralUtility::trimExplode(',', $record[$field], true);
            if (array_intersect($selectedModules, $aliasIdentifiers) !== []) {
                return true;
            }
        }
        return false;
    }

    protected function updateRecords(string $table, string $field): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable($table);
        $statement = $this->getPreparedQueryBuilder($table, $field)->select('uid', $field)->executeQuery();
        $aliasIdentifiers = array_keys($this->aliases);
        while ($record = $statement->fetchAssociative()) {
            $selectedModules = GeneralUtility::trimExplode(',', $record[$field], true);
            if (array_intersect($selectedModules, $aliasIdentifiers) === []) {
                continue;
            }
            $newModules = [];
            foreach ($selectedModules as $moduleIdentifier) {
                $newModules[] = $this->aliases[$moduleIdentifier] ?? $moduleIdentifier;
            }
            $connection->update($table, [$field => implode(',', $newModules)], ['uid' => $record['uid']]);
        }
    }

    protected function getPreparedQueryBuilder(string $table, string $field): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from($table)
            ->where(
                $queryBuilder->expr()->isNotNull($field),
                $queryBuilder->expr()->neq($field, $queryBuilder->createNamedParameter('')),
            );

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
