<?php

use TYPO3\CMS\Belog\Controller\BackendLogController;

/**
 * Definitions for modules provided by EXT:belog
 */
return [
    'system_log' => [
        'parent' => 'system',
        'access' => 'user',
        'iconIdentifier' => 'module-belog',
        'labels' => 'LLL:EXT:belog/Resources/Private/Language/locallang_mod.xlf',
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
