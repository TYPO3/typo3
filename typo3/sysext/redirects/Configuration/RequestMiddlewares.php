<?php

/**
 * Definitions for middlewares provided by EXT:redirects
 */
return [
    'frontend' => [
        'typo3/cms-redirects/redirecthandler' => [
            'target' => \TYPO3\CMS\Redirects\Http\Middleware\RedirectHandler::class,
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
