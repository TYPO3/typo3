<?php

use TYPO3\CMS\Redirects\Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all AJAX-based routes for entry points
 *
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [

    // Revert Correlation
    'redirects_revert_correlation' => [
        'path' => '/redirects/revert/correlation',
        'target' => Controller\RecordHistoryRollbackController::class . '::revertCorrelation',
    ],

    // Endpoint to generate a Short URL
    'short_url_generate' => [
        'path' => '/short-url/generate',
        'methods' => ['POST'],
        'target' => Controller\ShortUrlGeneratorController::class . '::generate',
    ],

    // Endpoint to validate a Short URL for uniqueness
    'short_url_validate' => [
        'path' => '/short-url/validate',
        'methods' => ['POST'],
        'target' => Controller\ShortUrlGeneratorController::class . '::validate',
    ],
];
