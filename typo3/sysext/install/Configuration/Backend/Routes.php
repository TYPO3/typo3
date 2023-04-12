<?php

use TYPO3\CMS\Install\Controller\ServerResponseCheckController;

/**
 * Defines routes for Install Tool being called from backend context.
 */

return [
    'install.server-response-check.host' => [
        'access' => 'public',
        'path' => '/install/server-response-check/host',
        'target' => ServerResponseCheckController::class . '::checkHostAction',
    ],
];
