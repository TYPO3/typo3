<?php

use TYPO3\CMS\Install\Controller\BackendModuleController;
use TYPO3\CMS\Install\Controller\ServerResponseCheckController;

/**
 * Defines routes for Install Tool being called from backend context.
 */

return [
    'install.backend-user-confirmation' => [
        'path' => '/install/backend-user-confirmation',
        'target' => BackendModuleController::class . '::backendUserConfirmationAction',
    ],
    'install.server-response-check.host' => [
        'access' => 'public',
        'path' => '/install/server-response-check/host',
        'target' => ServerResponseCheckController::class . '::checkHostAction',
    ],
];
