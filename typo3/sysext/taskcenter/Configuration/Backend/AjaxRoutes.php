<?php

/**
 * Definitions for routes provided by EXT:taskcenter
 */
return [
    'taskcenter_collapse' => [
        'path' => '/taskcenter/collapse',
        'target' => \TYPO3\CMS\Taskcenter\Controller\TaskStatusController::class . '::saveCollapseState',
    ],
    'taskcenter_sort' => [
        'path' => '/taskcenter/sort',
        'target' => \TYPO3\CMS\Taskcenter\Controller\TaskStatusController::class . '::saveSortingState',
    ],
];
