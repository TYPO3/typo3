<?php
/**
 * Definitions for middlewares provided by EXT:workspaces
 */
return [
    'frontend' => [
        'typo3/cms-workspaces/preview' => [
            'target' => \TYPO3\CMS\Workspaces\Middleware\WorkspacePreview::class,
            'after' => [
                // A preview user will override an existing logged-in backend user
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                // Page Router should have not been called yet, in order to set up the Context's Workspace Aspect
                'typo3/cms-frontend/page-resolver',
            ],
        ],
        'typo3/cms-workspaces/preview-permissions' => [
            'target' => \TYPO3\CMS\Workspaces\Middleware\WorkspacePreviewPermissions::class,
            'after' => [
                // The cookie/GET parameter information should have been evaluated
                'typo3/cms-workspaces/preview',
                // PageArguments are needed to find out the current Page ID
                'typo3/cms-frontend/page-resolver',
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                // TSFE should not yet have been called - determineId() is what relies on the information of this middleware
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
