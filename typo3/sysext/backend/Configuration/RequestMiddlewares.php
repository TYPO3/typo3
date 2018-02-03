<?php
/**
 * An array consisting of implementations of middlewares for a middleware stack to be registered
 *
 *  'stackname' => [
 *      'middleware-identifier' => [
 *         'target' => classname or callable
 *         'before/after' => array of dependencies
 *      ]
 *   ]
 */
return [
    'backend' => [
        'typo3/cms-backend/locked-backend' => [
            'target' => \TYPO3\CMS\Backend\Middleware\LockedBackendGuard::class,
        ],
        'typo3/cms-backend/https-redirector' => [
            'target' => \TYPO3\CMS\Backend\Middleware\ForcedHttpsBackendRedirector::class,
            'after' => [
                'typo3/cms-backend/locked-backend'
            ]
        ],
        'typo3/cms-backend/backend-routing' => [
            'target' => \TYPO3\CMS\Backend\Middleware\BackendRouteInitialization::class,
            'after' => [
                'typo3/cms-backend/https-redirector'
            ]
        ],
        'typo3/cms-backend/authentication' => [
            'target' => \TYPO3\CMS\Backend\Middleware\BackendUserAuthenticator::class,
            'after' => [
                'typo3/cms-backend/backend-routing'
            ]
        ],
        'typo3/cms-backend/legacy-document-template' => [
            'target' => \TYPO3\CMS\Backend\Middleware\LegacyBackendTemplateInitialization::class,
            'after' => [
                'typo3/cms-backend/authentication'
            ]
        ],
        'typo3/cms-backend/output-compression' => [
            'target' => \TYPO3\CMS\Backend\Middleware\OutputCompression::class,
            'after' => [
                'typo3/cms-backend/authentication'
            ]
        ],
        'typo3/cms-backend/response-headers' => [
            'target' => \TYPO3\CMS\Backend\Middleware\AdditionalResponseHeaders::class,
            'after' => [
                'typo3/cms-backend/output-compression'
            ]
        ],
    ]
];
