<?php
/**
 * Definitions for middlewares provided by EXT:workspaces
 */
return [
    'frontend' => [
        'typo3/cms-workspaces/preview' => [
            'target' => \TYPO3\CMS\Workspaces\Middleware\WorkspacePreview::class,
            'after' => [
                // PageArguments are needed to store information about the preview
                'typo3/cms-frontend/page-argument-validator',
                // A preview user will override an existing logged-in backend user
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                // TSFE is needed to store information about the preview
                'typo3/cms-frontend/tsfe',
            ]
        ],
    ]
];
