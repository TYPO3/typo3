<?php

use TYPO3\CMS\Reactions\Controller\ManagementController;

/**
 * Definitions for AJAX routes provided by EXT:reactions
 */
return [
    'reactions_try_out' => [
        'path' => '/reactions/try-out',
        'target' => ManagementController::class . '::tryOutAction',
        'inheritAccessFromModule' => 'integrations_reactions',
    ],
    'reactions_dry_run' => [
        'path' => '/reactions/dry-run',
        'methods' => ['POST'],
        'target' => ManagementController::class . '::resolveDryRunAction',
        'inheritAccessFromModule' => 'integrations_reactions',
    ],
];
