<?php

use TYPO3\CMS\Dashboard\Controller\DashboardController;

/**
 * Definitions for modules provided by EXT:dashboard
 */
return [
    'dashboard' => [
        'position' => ['before' => '*'],
        'standalone' => true,
        'access' => 'user',
        'path' => '/module/dashboard',
        'iconIdentifier' => 'module-dashboard',
        'labels' => 'dashboard.module',
        'routes' => [
            '_default' => [
                'target' => DashboardController::class . '::mainAction',
            ],
        ],
    ],
];
