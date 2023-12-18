<?php

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Definitions for modules provided by EXT:scheduler
 */
return [
    'scheduler' => [
        'parent' => 'system',
        'access' => 'admin',
        'path' => '/module/scheduler',
        'iconIdentifier' => 'module-scheduler',
        'labels' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => SchedulerModuleController::class . '::handleRequest',
            ],
        ],
    ],
    'scheduler_manage' => [
        'parent' => 'scheduler',
        'access' => 'admin',
        'path' => '/module/scheduler/manage',
        'labels' => ['title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.scheduler'],
        'routes' => [
            '_default' => [
                'target' => SchedulerModuleController::class . '::handleRequest',
            ],
        ],
        'aliases' => ['system_txschedulerM1'],
    ],
    'scheduler_availabletasks' => [
        'parent' => 'scheduler',
        'access' => 'admin',
        'path' => '/module/scheduler/available-tasks',
        'labels' => ['title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.info'],
        'routes' => [
            '_default' => [
                'target' => \TYPO3\CMS\Scheduler\Controller\AvailableSchedulerTasksController::class . '::handle',
            ],
        ],
    ],
    'scheduler_setupcheck' => [
        'parent' => 'scheduler',
        'access' => 'admin',
        'path' => '/module/scheduler/check-setup',
        'labels' => ['title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.check'],
        'routes' => [
            '_default' => [
                'target' => \TYPO3\CMS\Scheduler\Controller\SchedulerSetupCheckController::class . '::handle',
            ],
        ],
    ],
];
