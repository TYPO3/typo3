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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service handling clearing and statistics of semi-persistent
 * core tables.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ClearTableService
{
    /**
     * @var array List of table and their description
     */
    protected $tableList = [
        [
            'name' => 'be_sessions',
            'description' => 'Backend user sessions',
        ],
        [
            'name' => 'fe_sessions',
            'description' => 'Frontend user sessions',
        ],
        [
            'name' => 'sys_history',
            'description' => 'Tracking of database record changes through TYPO3 backend forms',
        ],
        [
            'name' => 'sys_lockedrecords',
            'description' => 'Record locking of backend user editing',
        ],
        [
            'name' => 'sys_log',
            'description' => 'General log table',
        ],
        [
            'name' => 'sys_preview',
            'description' => 'Workspace preview links',
        ],
        [
            'name' => 'tx_extensionmanager_domain_model_extension',
            'description' => 'List of TER extensions',
        ],
    ];

    /**
     * Get an array of all affected tables, a short description and their row counts
     *
     * @return array Details per table
     */
    public function getTableStatistics(): array
    {
        $tableStatistics = [];
        foreach ($this->tableList as $table) {
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
     *
     * @param string $tableName
     * @throws \RuntimeException
     */
    public function clearSelectedTable(string $tableName)
    {
        $tableFound = false;
        foreach ($this->tableList as $table) {
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
}
