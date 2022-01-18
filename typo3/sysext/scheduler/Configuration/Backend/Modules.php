<?php

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Definitions for modules provided by EXT:scheduler
 */
return [
    'system_txschedulerM1' => [
        'parent' => 'system',
        'access' => 'admin',
        'path' => '/module/system/scheduler',
        'iconIdentifier' => 'module-scheduler',
        'labels' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => SchedulerModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
