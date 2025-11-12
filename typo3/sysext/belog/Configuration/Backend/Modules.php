<?php

use TYPO3\CMS\Belog\Controller\BackendLogController;

/**
 * Definitions for modules provided by EXT:belog
 */
return [
    'system_log' => [
        'parent' => 'tools',
        'access' => 'user',
        'iconIdentifier' => 'module-belog',
        'labels' => 'belog.module',
        'path' => '/module/system/log',
        'aliases' => ['system_BelogLog'],
        'extensionName' => 'Belog',
        'controllerActions' => [
            BackendLogController::class => [
                'list', 'deleteMessage',
            ],
        ],
    ],
];
