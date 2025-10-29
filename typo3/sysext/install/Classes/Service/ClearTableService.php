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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service handling clearing and statistics of semi-persistent core tables.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final readonly class ClearTableService
{
    public function __construct(private FailsafePackageManager $packageManager) {}

    /**
     * Get an array of all affected tables, a short description and their row counts
     */
    public function getTableStatistics(): array
    {
        $tableStatistics = [];
        foreach ($this->getTableList() as $table) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table['name']);
            if ($connection->createSchemaManager()->tablesExist([$table['name']])) {
                $table['rowCount'] = $connection->count(
                    '*',
                    $table['name'],
                    []
                );
                $tableStatistics[] = $table;
            }
        }
        return $tableStatistics;
    }

    /**
     * Truncate a table from $this->tableList
     */
    public function clearSelectedTable(string $tableName): void
    {
        $tableFound = false;
        foreach ($this->getTableList() as $table) {
            if ($table['name'] === $tableName) {
                $tableFound = true;
                break;
            }
        }
        if (!$tableFound) {
            throw new \RuntimeException(
                'Selected table ' . $tableName . ' can not be cleared',
                1501942151
            );
        }
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)->truncate($tableName);
    }

    /**
     * List of tables and their description
     */
    private function getTableList(): array
    {
        $tableList = [
            [
                'name' => 'be_sessions',
                'description' => 'Backend user sessions',
            ],
            [
                'name' => 'fe_sessions',
                'description' => 'Frontend user sessions',
            ],
            [
                'name' => 'sys_lockedrecords',
                'description' => 'Record locking of backend user editing',
            ],
            [
                'name' => 'sys_log',
                'description' => 'General log table',
            ],
        ];
        if ($this->packageManager->isPackageActive('workspaces')) {
            $tableList[] = [
                'name' => 'sys_preview',
                'description' => 'Workspace preview links',
            ];
        }
        if ($this->packageManager->isPackageActive('extensionmanager')) {
            $tableList[] = [
                'name' => 'tx_extensionmanager_domain_model_extension',
                'description' => 'List of TER extensions',
            ];
        }
        return $tableList;
    }
}
