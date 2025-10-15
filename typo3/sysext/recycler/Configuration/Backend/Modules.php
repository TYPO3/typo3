<?php

use TYPO3\CMS\Recycler\Controller\RecyclerModuleController;

/**
 * Definitions for modules provided by EXT:recycler
 */
return [
    'recycler' => [
        'parent' => 'content',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/recycler',
        'iconIdentifier' => 'module-recycler',
        'labels' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf',
        'aliases' => ['web_RecyclerRecycler'],
        'routes' => [
            '_default' => [
                'target' => RecyclerModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
