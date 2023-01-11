<?php

use TYPO3\CMS\Webhooks\Controller\ManagementController;

/**
 * Definitions for modules provided by EXT:webhooks
 */
return [
    'webhooks_management' => [
        'parent' => 'system',
        'position' => ['after' => 'system_BeuserTxBeuser'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/webhooks',
        'iconIdentifier' => 'module-webhooks',
        'labels' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_module_webhooks.xlf',
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::overviewAction',
            ],
        ],
    ],
];
