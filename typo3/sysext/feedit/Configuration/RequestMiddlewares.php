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
        'typo3/cms-frontendedit/initiator' => [
            'target' => \TYPO3\CMS\Feedit\Middleware\FrontendEditInitiator::class,
            'after' => [
                'typo3/cms-adminpanel/initiator',
                'typo3/cms-frontend/page-resolver',
            ]
        ],
    ]
];
