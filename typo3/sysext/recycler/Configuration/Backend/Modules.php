<?php

use TYPO3\CMS\Recycler\Controller\RecyclerModuleController;

/**
 * Definitions for modules provided by EXT:recycler
 */
return [
    'recycler' => [
        'parent' => 'content',
        'position' => ['after' => 'content_status'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/recycler',
        'iconIdentifier' => 'module-recycler',
        'labels' => 'recycler.module',
        'aliases' => ['web_RecyclerRecycler'],
        'routes' => [
            '_default' => [
                'target' => RecyclerModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
