<?php

use TYPO3\CMS\Reactions\Controller\ManagementController;

/**
 * Definitions for modules provided by EXT:reactions
 */
return [
    'system_reactions' => [
        'parent' => 'system',
        'position' => ['after' => 'backend_user_management'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/reactions',
        'iconIdentifier' => 'module-reactions',
        'labels' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_module_reactions.xlf',
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::handleRequest',
            ],
        ],
    ],
];
