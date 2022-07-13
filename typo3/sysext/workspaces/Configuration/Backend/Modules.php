<?php

use TYPO3\CMS\Workspaces\Controller\ReviewController;

/**
 * Definitions for modules provided by EXT:workspaces
 */
return [
    'workspaces_admin' => [
        'parent' => 'web',
        'position' => ['before' => 'web_info'],
        'access' => 'user',
        'path' => '/module/manage/workspaces',
        'iconIdentifier' => 'module-workspaces',
        'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
        'aliases' => ['web_WorkspacesWorkspaces'],
        'routes' => [
            '_default' => [
                'target' => ReviewController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'language' => 'all',
            'depth' => 1,
            'stage' => -99,
        ],
    ],
];
