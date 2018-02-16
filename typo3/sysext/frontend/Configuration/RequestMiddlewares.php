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
    'frontend' => [
        'typo3/cms-core/legacy-request-handler-dispatcher' => [
            'target' => \TYPO3\CMS\Core\Middleware\LegacyRequestHandlerDispatcher::class,
        ],
        'typo3/cms-frontend/timetracker' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\TimeTrackerInitialization::class,
            'after' => [
                'typo3/cms-core/legacy-request-handler-dispatcher'
            ],
        ],
        'typo3/cms-core/normalized-params-attribute' => [
            'target' => \TYPO3\CMS\Core\Middleware\NormalizedParamsAttribute::class,
            'after' => [
                'typo3/cms-frontend/timetracker',
            ]
        ],
        'typo3/cms-frontend/preprocessing' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PreprocessRequestHook::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ]
        ],
        'typo3/cms-frontend/eid' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\EidHandler::class,
            'after' => [
                'typo3/cms-frontend/preprocessing'
            ]
        ],
        'typo3/cms-frontend/maintenance-mode' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\MaintenanceMode::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-frontend/eid'
            ]
        ],
        'typo3/cms-frontend/content-length-headers' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\ContentLengthResponseHeader::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode'
            ]
        ],
        'typo3/cms-frontend/tsfe' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\TypoScriptFrontendInitialization::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ]
        ],
        'typo3/cms-frontend/output-compression' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\OutputCompression::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ]
        ],
        'typo3/cms-frontend/authentication' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\FrontendUserAuthenticator::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ]
        ],
    ]
];
