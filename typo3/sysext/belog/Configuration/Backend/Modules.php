<?php

use TYPO3\CMS\Belog\Controller\BackendLogController;

/**
 * Definitions for modules provided by EXT:belog
 */
return [
    'system_BelogLog' => [
        'parent' => 'system',
        'access' => 'user',
        'iconIdentifier' => 'module-belog',
        'labels' => 'LLL:EXT:belog/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Belog',
        'controllerActions' => [
            BackendLogController::class => [
                'list', 'deleteMessage',
            ],
        ],
    ],
];
