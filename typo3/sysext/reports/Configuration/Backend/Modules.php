<?php

use TYPO3\CMS\Reports\Controller\RecordStatisticsController;
use TYPO3\CMS\Reports\Controller\StatusReportController;

/**
 * Definitions for modules provided by EXT:reports
 */
return [
    'system_reports' => [
        'parent' => 'system',
        'access' => 'admin',
        'path' => '/module/system/reports',
        'iconIdentifier' => 'module-reports',
        'labels' => 'reports.modules.overview',
        'showSubmoduleOverview' => true,
    ],
    'system_reports_status' => [
        'parent' => 'system_reports',
        'access' => 'admin',
        'path' => '/module/system/reports/status',
        'iconIdentifier' => 'module-reports',
        'labels' => 'reports.modules.status',
        'routes' => [
            '_default' => [
                'target' => StatusReportController::class . '::handleRequest',
            ],
        ],
    ],
    'system_reports_statistics' => [
        'parent' => 'system_reports',
        'access' => 'admin',
        'path' => '/module/system/reports/statistics',
        'iconIdentifier' => 'module-reports',
        'labels' => 'reports.modules.statistics',
        'routes' => [
            '_default' => [
                'target' => RecordStatisticsController::class . '::handleRequest',
            ],
        ],
    ],
];
