<?php

use TYPO3\CMS\Scheduler\Controller\NewSchedulerTaskController;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Definitions for routes provided by EXT:scheduler
 */
return [
    // Register new scheduler task wizard (used in a modal)
    'new_scheduler_task_wizard' => [
        'path' => '/scheduler/task/wizard/new',
        'target' => NewSchedulerTaskController::class . '::handleRequest',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'scheduler',
    ],
    // Register scheduler setup check (used in a modal)
    'scheduler_setup_check' => [
        'path' => '/scheduler/setup-check',
        'target' => SchedulerModuleController::class . '::setupCheckAction',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'scheduler',
    ],
];
