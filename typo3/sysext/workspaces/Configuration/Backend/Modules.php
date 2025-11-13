<?php

use TYPO3\CMS\Workspaces\Controller\ReviewController;

/**
 * Definitions for modules provided by EXT:workspaces
 */
return [
    'workspaces_publish' => [
        'parent' => 'content',
        'position' => ['after' => 'page_preview'],
        'access' => 'user',
        'workspaces' => 'offline',
        'path' => '/module/manage/workspaces',
        'iconIdentifier' => 'module-workspaces',
        'labels' => 'workspaces.module',
        'aliases' => ['workspaces_admin', 'web_WorkspacesWorkspaces'],
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
