<?php

/**
 * Definitions for routes provided by EXT:workspaces
 */
return [
    // Set the workspace
    'workspace_switch' => [
        'path' => '/workspace/switch',
        'target' => \TYPO3\CMS\Workspaces\Controller\AjaxController::class . '::switchWorkspaceAction'
    ]
];
