<?php

use TYPO3\CMS\Core\Controller\RequireJsController;

/**
 * Definitions for routes provided by EXT:core
 */
return [
    // dynamically load requirejs module definitions
    // @deprecated will be removed in TYPO3 v13.0.
    'core_requirejs' => [
        'path' => '/core/requirejs',
        'access' => 'public',
        'target' => RequireJsController::class . '::retrieveConfiguration',
    ],
];
