<?php

use TYPO3Tests\PlaywrightHelper\Middleware\PlaywrightHelperMiddleware;

/**
 * Register the Playwright helper middleware in the backend stack. The endpoints
 * live under /typo3/playwright-helper/ and are therefore only reachable through
 * the backend middleware chain.
 */
return [
    'backend' => [
        'typo3tests/playwright-helper' => [
            'target' => PlaywrightHelperMiddleware::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-backend/locked-backend',
            ],
        ],
    ],
];
