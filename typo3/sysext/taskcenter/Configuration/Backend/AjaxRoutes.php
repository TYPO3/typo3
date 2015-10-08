<?php

/**
 * Definitions for routes provided by EXT:taskcenter
 */
return [
    // Collapse
    'taskcenter_collapse' => [
        'path' => '/taskcenter/collapse',
        'target' => \TYPO3\CMS\Taskcenter\TaskStatus::class . '::saveCollapseState'
    ],

    // Sort
    'taskcenter_sort' => [
        'path' => '/taskcenter/sort',
        'target' => \TYPO3\CMS\Taskcenter\TaskStatus::class . '::saveSortingState'
    ]
];
