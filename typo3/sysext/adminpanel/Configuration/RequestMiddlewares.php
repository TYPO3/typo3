<?php
/**
 * An array consisting of implementations of middlewares for a middleware stack to be registered
 *  'stackname' => [
 *      'middleware-identifier' => [
 *         'target' => classname or callable
 *         'before/after' => array of dependencies
 *      ]
 *   ]
 */
return [
    'frontend' => [
        'typo3/cms-adminpanel/initiator' => [
            'target' => \TYPO3\CMS\Adminpanel\Middleware\AdminPanelInitiator::class,
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
                'typo3/cms-frontend/page-resolver'
            ],
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
            ]
        ],
        'typo3/cms-adminpanel/sql-logging' => [
            'target' => \TYPO3\CMS\Adminpanel\Middleware\SqlLogging::class,
            'after' => [
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                'typo3/cms-frontend/site'
            ]
        ],
        'typo3/cms-adminpanel/data-persister' => [
            'target' => \TYPO3\CMS\Adminpanel\Middleware\AdminPanelDataPersister::class,
            'after' => [
                'typo3/cms-adminpanel/initiator',
                'typo3/cms-frontend/content-length-headers',
                'typo3/cms-adminpanel/renderer'
            ],
        ],
        'typo3/cms-adminpanel/renderer' => [
            'target' => \TYPO3\CMS\Adminpanel\Middleware\AdminPanelRenderer::class,
            'after' => [
                'typo3/cms-adminpanel/initiator',
                'typo3/cms-frontend/content-length-headers'
            ]
        ],
    ]
];
