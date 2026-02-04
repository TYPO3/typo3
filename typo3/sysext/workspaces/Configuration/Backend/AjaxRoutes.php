<?php

/**
 * Definitions for routes provided by EXT:workspaces
 */
return [
    // Get workspace information (current workspace and available workspaces)
    'workspace_info' => [
        'path' => '/workspace/info',
        'methods' => ['GET'],
        'target' => \TYPO3\CMS\Workspaces\Controller\WorkspacesAjaxController::class . '::getWorkspaceInfoAction',
    ],
    // Set the workspace
    'workspace_switch' => [
        'path' => '/workspace/switch',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Workspaces\Controller\WorkspacesAjaxController::class . '::switchWorkspaceAction',
    ],
    'workspace_dispatch' => [
        'path' => '/workspace/dispatch',
        'target' => \TYPO3\CMS\Workspaces\Controller\WorkspacesAjaxController::class . '::dispatch',
        'inheritAccessFromModule' => 'workspaces_publish',
    ],
    'workspace_preview' => [
        'path' => '/workspace/preview',
        'target' => \TYPO3\CMS\Workspaces\Controller\WorkspacesAjaxController::class . '::preview',
    ],
];
