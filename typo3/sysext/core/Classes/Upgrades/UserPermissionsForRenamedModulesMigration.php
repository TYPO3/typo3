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

namespace TYPO3\CMS\Core\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @since 14.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('userPermissionsForRenamedModulesMigration')]
class UserPermissionsForRenamedModulesMigration implements UpgradeWizardInterface
{
    protected array $tables = [
        'be_groups' => 'groupMods',
        'be_users' => 'userMods',
    ];

    /**
     * @var array <string, string> an array with the old module identifier as key and the new one as value
     */
    protected array $moduleRenaming = [
        'web_list' => 'records',
        'web_info' => 'content_status',
        'workspaces_admin' => 'workspaces_publish',
    ];

    public function getTitle(): string
    {
        return 'Migrate module permissions';
    }

    public function getDescription(): string
    {
        return 'Migrate permissions for renamed modules in user and group module permissions.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->migrate(true);
    }

    public function executeUpdate(): bool
    {
        return $this->migrate(false);
    }

    private function migrate(bool $dryRun): bool
    {
        $migrated = false;
        foreach ($this->tables as $table => $field) {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
            $connection = $this->getConnectionPool()->getConnectionForTable($table);
            $queryBuilder->select('uid', $field)
                ->from($table)
                ->executeQuery();

            foreach ($queryBuilder->fetchAllAssociative() as $record) {
                $originalModules = (string)($record[$field] ?? '');
                $modules = explode(',', $originalModules);
                $updatedModules = array_map(function ($module) {
                    return $this->moduleRenaming[trim($module)] ?? $module;
                }, $modules);
                $newModules = implode(',', $updatedModules);
                if ($originalModules !== $newModules) {
                    if ($dryRun) {
                        return true;
                    }
                    $migrated = $connection->update(
                        $table,
                        [
                            $field => $newModules,
                        ],
                        ['uid' => (int)$record['uid']]
                    ) > 0 || $migrated;
                }
            }
        }
        return $migrated;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
