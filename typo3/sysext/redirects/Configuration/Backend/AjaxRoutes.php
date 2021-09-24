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

];
