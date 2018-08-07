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
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
            ]
        ],
        'typo3/cms-adminpanel/sql-logging' => [
            'target' => \TYPO3\CMS\Adminpanel\Middleware\SqlLogging::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
            ]
        ],
    ]
];
