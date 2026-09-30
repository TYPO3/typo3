<?php

use TYPO3\CMS\Install\Controller\EntryPointRedirectController;
use TYPO3\CMS\Install\Controller\ServerResponseCheckController;

/**
 * Defines routes for Install Tool being called from backend context.
 */

return [
    'install.server-response-check.host' => [
        'access' => 'anonymous',
        'path' => '/install/server-response-check/host',
        'target' => ServerResponseCheckController::class . '::checkHostAction',
    ],
    'install.redirect' => [
        'access' => 'anonymous',
        'path' => '/install',
        'target' => EntryPointRedirectController::class . '::redirectAction',
    ],
    'install.php.redirect' => [
        'access' => 'anonymous',
        'path' => '/install.php',
        'target' => EntryPointRedirectController::class . '::redirectAction',
    ],
];
