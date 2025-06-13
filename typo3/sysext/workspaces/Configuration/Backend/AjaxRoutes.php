<?php

/**
 * Definitions for routes provided by EXT:workspaces
 */
return [
    // Set the workspace
    'workspace_switch' => [
        'path' => '/workspace/switch',
        'target' => \TYPO3\CMS\Workspaces\Controller\WorkspacesToolbarController::class . '::switchWorkspaceAction',
    ],
    'workspace_dispatch' => [
        'path' => '/workspace/dispatch',
        'target' => \TYPO3\CMS\Workspaces\Controller\WorkspacesAjaxController::class . '::dispatch',
    ],
];
