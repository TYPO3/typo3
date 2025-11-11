<?php

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Definitions for modules provided by EXT:scheduler
 */
return [
    'scheduler' => [
        'parent' => 'system',
        'access' => 'admin',
        'path' => '/module/system/scheduler',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-scheduler',
        'labels' => 'scheduler.module',
        'routes' => [
            '_default' => [
                'target' => SchedulerModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
