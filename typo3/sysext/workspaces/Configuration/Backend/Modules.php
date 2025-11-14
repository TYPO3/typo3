<?php

use TYPO3\CMS\Workspaces\Controller\ReviewController;

/**
 * Definitions for modules provided by EXT:workspaces
 */
return [
    'workspaces_admin' => [
        'parent' => 'content',
        'position' => ['before' => 'content_status'],
        'access' => 'user',
        'workspaces' => 'offline',
        'path' => '/module/manage/workspaces',
        'iconIdentifier' => 'module-workspaces',
        'labels' => 'workspaces.module',
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
