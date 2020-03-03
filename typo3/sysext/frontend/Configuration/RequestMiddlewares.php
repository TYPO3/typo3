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
        /** internal: do not use or reference this middleware in your own code */
        'typo3/cms-frontend/timetracker' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\TimeTrackerInitialization::class,
        ],
        /** internal: do not use or reference this middleware in your own code */
        'typo3/cms-core/normalized-params-attribute' => [
            'target' => \TYPO3\CMS\Core\Middleware\NormalizedParamsAttribute::class,
            'after' => [
                'typo3/cms-frontend/timetracker',
            ]
        ],
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
        'typo3/cms-frontend/preprocessing' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PreprocessRequestHook::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ]
        ],
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
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
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
        'typo3/cms-frontend/content-length-headers' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\ContentLengthResponseHeader::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode'
            ]
        ],
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
        'typo3/cms-frontend/tsfe' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\TypoScriptFrontendInitialization::class,
            'after' => [
                'typo3/cms-frontend/eid',
            ]
        ],
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
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
        'typo3/cms-frontend/backend-user-authentication' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\BackendUserAuthenticator::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ]
        ],
        'typo3/cms-frontend/preview-simulator' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PreviewSimulator::class,
            'after' => [
                'typo3/cms-frontend/backend-user-authentication',
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver'
            ]
        ],
        'typo3/cms-frontend/site' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\SiteResolver::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver'
            ]
        ],
        'typo3/cms-frontend/base-redirect-resolver' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\SiteBaseRedirectResolver::class,
            'after' => [
                'typo3/cms-frontend/site-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/static-route-resolver'
            ]
        ],
        'typo3/cms-frontend/static-route-resolver' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\StaticRouteResolver::class,
            'after' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver'
            ]
        ],
        'typo3/cms-frontend/page-resolver' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PageResolver::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
                'typo3/cms-frontend/site',
            ]
        ],
        'typo3/cms-frontend/page-argument-validator' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PageArgumentValidator::class,
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ]
        ],
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
        'typo3/cms-frontend/prepare-tsfe-rendering' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PrepareTypoScriptFrontendRendering::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ]
        ],
        /** internal: do not use or reference this middleware in your own code, as this will be possibly be removed */
        'typo3/cms-frontend/shortcut-and-mountpoint-redirect' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\ShortcutAndMountPointRedirect::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
            'before' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ]
];
