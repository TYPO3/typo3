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
        'typo3/cms-frontend/timetracker' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\TimeTrackerInitialization::class,
        ],
        'typo3/cms-frontend/preprocessing' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\PreprocessRequestHook::class,
            'after' => [
                'typo3/cms-frontend/timetracker'
            ]
        ],
        'typo3/cms-frontend/eid' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\EidHandler::class,
            'after' => [
                'typo3/cms-frontend/preprocessing'
            ]
        ],
        'typo3/cms-frontend/content-length-headers' => [
            'target' => \TYPO3\CMS\Frontend\Middleware\ContentLengthResponseHeader::class,
            'after' => [
                'typo3/cms-frontend/eid'
            ]
        ],
    ]
];
