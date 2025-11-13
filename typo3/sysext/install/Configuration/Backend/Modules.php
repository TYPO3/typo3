<?php

use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime;
use TYPO3\CMS\Install\Controller\BackendModuleController;

/**
 * Definitions for modules provided by EXT:insatall
 */
return [
    'system_maintenance' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'position' => ['before' => '*'],
        'path' => '/module/system/maintenance',
        'aliases' => ['tools_toolsmaintenance'],
        'iconIdentifier' => 'module-install-maintenance',
        'labels' => 'install.modules.maintenance',
        'routes' => [
            '_default' => [
                'target' => BackendModuleController::class . '::maintenanceAction',
            ],
        ],
        'routeOptions' => [
            'sudoMode' => [
                'group' => 'systemMaintainer',
                'lifetime' => AccessLifetime::medium,
            ],
        ],
    ],
    'system_settings' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'position' => ['before' => '*'],
        'path' => '/module/system/settings',
        'aliases' => ['tools_toolssettings'],
        'iconIdentifier' => 'module-install-settings',
        'labels' => 'install.modules.settings',
        'routes' => [
            '_default' => [
                'target' => BackendModuleController::class . '::settingsAction',
            ],
        ],
        'routeOptions' => [
            'sudoMode' => [
                'group' => 'systemMaintainer',
                'lifetime' => AccessLifetime::medium,
            ],
        ],
    ],
    'system_upgrade' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'position' => ['before' => '*'],
        'path' => '/module/system/upgrade',
        'aliases' => ['tools_toolsupgrade'],
        'iconIdentifier' => 'module-install-upgrade',
        'labels' => 'install.modules.upgrade',
        'routes' => [
            '_default' => [
                'target' => BackendModuleController::class . '::upgradeAction',
            ],
        ],
        'routeOptions' => [
            'sudoMode' => [
                'group' => 'systemMaintainer',
                'lifetime' => AccessLifetime::medium,
            ],
        ],
    ],
    'system_environment' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'position' => ['before' => '*'],
        'path' => '/module/system/environment',
        'aliases' => ['tools_toolsenvironment'],
        'iconIdentifier' => 'module-install-environment',
        'labels' => 'install.modules.environment',
        'routes' => [
            '_default' => [
                'target' => BackendModuleController::class . '::environmentAction',
            ],
        ],
        'routeOptions' => [
            'sudoMode' => [
                'group' => 'systemMaintainer',
                'lifetime' => AccessLifetime::medium,
            ],
        ],
    ],
];
