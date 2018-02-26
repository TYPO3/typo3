<?php
/**
 * Definitions for middlewares provided by EXT:workspaces
 */
return [
    'frontend' => [
        'typo3/cms-workspaces/preview' => [
            'target' => \TYPO3\CMS\Workspaces\Middleware\WorkspacePreview::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                // TSFE is needed to store information about the preview
                'typo3/cms-frontend/tsfe',
                // A preview user will override an existing logged-in backend user
                'typo3/cms-frontend/backend-user-authentication',
                // Ensure, when a preview text is added, that the content length headers are added later-on
                'typo3/cms-frontend/content-length-headers',
                'typo3/cms-frontend/output-compression'
            ]
        ],
    ]
];
