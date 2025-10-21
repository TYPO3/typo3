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
        'labels' => [
            'title' => 'LLL:EXT:reactions/Resources/Private/Language/Modules/reactions.xlf:title',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/Modules/reactions.xlf:description',
            'shortDescription' => 'LLL:EXT:reactions/Resources/Private/Language/Modules/reactions.xlf:shortDescription',
        ],
        'aliases' => ['system_reactions'],
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::handleRequest',
            ],
        ],
    ],
];
