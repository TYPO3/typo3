<?php

use TYPO3\CMS\Webhooks\Controller\ManagementController;

/**
 * Definitions for modules provided by EXT:webhooks
 */
return [
    'integrations_webhooks' => [
        'parent' => 'integrations',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/integrations/webhooks',
        'iconIdentifier' => 'module-webhooks',
        'labels' => [
            'title' => 'LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:title',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:description',
            'shortDescription' => 'LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:shortDescription',
        ],
        'aliases' => ['webhooks_management'],
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::overviewAction',
            ],
        ],
    ],
];
