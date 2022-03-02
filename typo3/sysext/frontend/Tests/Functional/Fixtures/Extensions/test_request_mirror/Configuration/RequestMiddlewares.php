<?php

return [
    'frontend' => [
        'typo3/request-mirror' => [
            'target' => \TYPO3\RequestMirror\Middleware\RequestMirror::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/eid',
            ],
        ],
    ],
];
