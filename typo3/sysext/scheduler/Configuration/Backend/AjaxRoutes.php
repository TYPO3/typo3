<?php

use TYPO3\CMS\Scheduler\Controller\NewSchedulerTaskController;

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
];
