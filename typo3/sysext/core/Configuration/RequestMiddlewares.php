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
    'core' => [
        /** internal: do not use or reference this middleware in your own code */
        'typo3/cms-core/verify-host-header' => [
            'target' => \TYPO3\CMS\Core\Middleware\VerifyHostHeader::class,
        ],
        /** internal: do not use or reference this middleware in your own code */
        'typo3/cms-core/normalized-params-attribute' => [
            'target' => \TYPO3\CMS\Core\Middleware\NormalizedParamsAttribute::class,
            'after' => [
                'typo3/cms-core/verify-host-header',
            ],
        ],
        /** internal: do not use or reference this middleware in your own code */
        'typo3/cms-core/response-propagation' => [
            'target' => \TYPO3\CMS\Core\Middleware\ResponsePropagation::class,
            'after' => [
                'typo3/cms-core/verify-host-header',
            ],
        ],
    ],
];
