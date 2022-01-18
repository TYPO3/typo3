<?php

use TYPO3\CMS\Recycler\Controller\RecyclerModuleController;

/**
 * Definitions for modules provided by EXT:recycler
 */
return [
    'web_RecyclerRecycler' => [
        'parent' => 'web',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/recycler',
        'iconIdentifier' => 'module-recycler',
        'labels' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => RecyclerModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
