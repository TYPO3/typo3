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
        'typo3/cms-frontend/preprocessing' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PreprocessRequestHook::class,
        ],
        'typo3/cms-frontend/timetracker' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\TimeTrackerInitialization::class,
            'after' => [
                'typo3/cms-frontend/preprocessing'
            ]
        ]
    ]
];
