<?php

use TYPO3\CMS\Lowlevel\Controller\ConfigurationController;
use TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController;

/**
 * Definitions for modules provided by EXT:lowlevel
 */
return [
    'system_dbint' => [
        'parent' => 'system',
        'position' => ['after' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/dbint',
        'iconIdentifier' => 'module-dbint',
        'labels' => 'lowlevel.modules.database_integrity',
        'routes' => [
            '_default' => [
                'target' => DatabaseIntegrityController::class . '::handleRequest',
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
