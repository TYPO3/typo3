<?php

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Definitions for modules provided by EXT:scheduler
 */
return [
    'scheduler' => [
        'parent' => 'admin',
        'position' => ['after' => 'backend_user_management', 'before' => 'permissions_pages'],
        'access' => 'admin',
        'path' => '/module/scheduler',
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
