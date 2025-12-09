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
        'site_redirects' => 'redirects',
    ];

    /**
     * Modules that require a parent module to be accessible.
     *
     * @var array<string, string> Key: the new module identifier, Value: required parent module
     */
    protected array $requiredParentModules = [
        'redirects' => 'link_management',
    ];

    public function getTitle(): string
    {
        return 'Migrate module permissions';
    }

    public function getDescription(): string
    {
        return 'Migrate permissions for renamed modules in user and group module permissions. '
            . 'Also adds required parent modules when a module has been moved to a new location in the module hierarchy.';
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
                $parentModulesToAdd = [];
                $updatedModules = array_map(function ($module) use (&$parentModulesToAdd) {
                    $trimmedModule = trim($module);
                    if (isset($this->moduleRenaming[$trimmedModule])) {
                        $newModule = $this->moduleRenaming[$trimmedModule];
                        if (isset($this->requiredParentModules[$newModule])) {
                            $parentModulesToAdd[] = $this->requiredParentModules[$newModule];
                        }
                        return $newModule;
                    }
                    return $module;
                }, $modules);

                $trimmedModules = array_filter(array_map('trim', $updatedModules));
                $deduplicatedModules = array_unique($trimmedModules);

                // Check if existing modules (already renamed in a previous migration) need parent modules
                // This handles the case where the wizard ran in v14.0 before $requiredParentModules existed
                foreach ($deduplicatedModules as $module) {
                    if (isset($this->requiredParentModules[$module])) {
                        $parentModulesToAdd[] = $this->requiredParentModules[$module];
                    }
                }

                // Add parent modules if not already present
                foreach (array_unique($parentModulesToAdd) as $parentModule) {
                    if (!in_array($parentModule, $deduplicatedModules, true)) {
                        $deduplicatedModules[] = $parentModule;
                    }
                }

                $newModules = implode(',', $deduplicatedModules);
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
