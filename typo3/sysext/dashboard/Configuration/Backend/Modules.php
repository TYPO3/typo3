<?php

use TYPO3\CMS\Dashboard\Controller\DashboardController;

/**
 * Definitions for modules provided by EXT:dashboard
 */
return [
    'dashboard' => [
        'position' => ['top'],
        'standalone' => true,
        'access' => 'user',
        'path' => '/module/dashboard',
        'iconIdentifier' => 'module-dashboard',
        'labels' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => DashboardController::class . '::handleRequest',
            ],
        ],
    ],
];
