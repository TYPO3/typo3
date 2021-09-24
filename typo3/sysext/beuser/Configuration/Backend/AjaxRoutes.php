<?php

/**
 * Definitions for routes provided by EXT:beuser
 */
return [
    // Dispatch the permissions actions
    'user_access_permissions' => [
        'path' => '/users/access/permissions',
        'target' => \TYPO3\CMS\Beuser\Controller\PermissionController::class . '::handleAjaxRequest',
    ],
];
