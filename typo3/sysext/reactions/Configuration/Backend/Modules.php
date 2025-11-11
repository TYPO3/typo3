<?php

use TYPO3\CMS\Reactions\Controller\ManagementController;

/**
 * Definitions for modules provided by EXT:reactions
 */
return [
    'integrations_reactions' => [
        'parent' => 'integrations',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/integrations/reactions',
        'iconIdentifier' => 'module-reactions',
        'labels' => 'reactions.module',
        'aliases' => ['system_reactions'],
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::handleRequest',
            ],
        ],
    ],
];
