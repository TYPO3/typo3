<?php

use TYPO3\CMS\Lowlevel\Controller\ConfigurationController;
use TYPO3\CMS\Lowlevel\Controller\QuerySearchController;
use TYPO3\CMS\Lowlevel\Controller\RawSearchController;

/**
 * Definitions for modules provided by EXT:lowlevel
 */
return [
    'system_database' => [
        'parent' => 'system',
        'position' => ['after' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/database',
        'iconIdentifier' => 'module-dbint',
        'aliases' => ['system_dbint'],
        'labels' => 'lowlevel.modules.database_integrity',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
    ],
    'system_database_raw' => [
        'parent' => 'system_database',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/database/raw',
        'iconIdentifier' => 'module-dbint',
        'labels' => 'lowlevel.modules.database_raw',
        'routes' => [
            '_default' => [
                'target' => RawSearchController::class . '::handleRequest',
            ],
        ],
    ],
    'system_database_query' => [
        'parent' => 'system_database',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/database/query',
        'iconIdentifier' => 'module-dbint',
        'labels' => 'lowlevel.modules.database_query',
        'routes' => [
            '_default' => [
                'target' => QuerySearchController::class . '::handleRequest',
            ],
        ],
    ],
    'system_config' => [
        'parent' => 'system',
        'position' => ['after' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/config',
        'iconIdentifier' => 'module-config',
        'labels' => 'lowlevel.modules.config',
        'routes' => [
            '_default' => [
                'target' => ConfigurationController::class . '::indexAction',
            ],
        ],
        'moduleData' => [
            'tree' => '',
        ],
    ],
];
