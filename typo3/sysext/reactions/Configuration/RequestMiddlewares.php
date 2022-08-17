<?php

/**
 * Definitions for middlewares provided by EXT:reactions
 */

use TYPO3\CMS\Reactions\Http\Middleware\ReactionResolver;

return [
    'backend' => [
        'typo3/cms-reactions/resolver' => [
            'target' => ReactionResolver::class,
            'before' => [
                'typo3/cms-backend/authentication',
            ],
        ],
    ],
];
