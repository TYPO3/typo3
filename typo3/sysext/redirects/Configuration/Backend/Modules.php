<?php

use TYPO3\CMS\Redirects\Controller\ManagementController;

/**
 * Definitions for modules provided by EXT:redirects
 */
return [
    'site_redirects' => [
        'parent' => 'site',
        'position' => ['after' => 'site_configuration'],
        'access' => 'user',
        'path' => '/module/site/redirects',
        'iconIdentifier' => 'module-redirects',
        'labels' => 'redirects.module',
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'redirectType' => 'default',
        ],
    ],
];
