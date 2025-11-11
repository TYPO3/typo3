<?php

use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime;
use TYPO3\CMS\Install\Controller\BackendModuleController;

/**
 * Definitions for modules provided by EXT:insatall
 */
return [
    'tools_toolsmaintenance' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'path' => '/module/tools/maintenance',
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
    'tools_toolssettings' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'path' => '/module/tools/settings',
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
    'tools_toolsupgrade' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'path' => '/module/tools/upgrade',
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
    'tools_toolsenvironment' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'path' => '/module/tools/environment',
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
