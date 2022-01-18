<?php

use TYPO3\CMS\Workspaces\Controller\ReviewController;

/**
 * Definitions for modules provided by EXT:workspaces
 */
return [
    'web_WorkspacesWorkspaces' => [
        'parent' => 'web',
        'position' => ['before' => 'web_info'],
        'access' => 'user',
        'path' => '/module/web/workspaces',
        'iconIdentifier' => 'module-workspaces',
        'labels' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => ReviewController::class . '::handleRequest',
            ],
        ],
    ],
];
