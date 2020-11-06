<?php

use TYPO3\CMS\Install\Controller\BackendModuleController;

/**
 * Defines routes for Install Tool being called from backend context.
 */

return [
    'install.backend-user-confirmation' => [
        'path' => '/install/backend-user-confirmation',
        'target' => BackendModuleController::class . '::backendUserConfirmationAction'
    ],
];
