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
        'labels' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf',
        'showSubmoduleOverview' => true,
    ],
    'system_reports_status' => [
        'parent' => 'system_reports',
        'access' => 'admin',
        'path' => '/module/system/reports/status',
        'iconIdentifier' => 'module-reports',
        'labels' => [
            'title' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_title',
            'shortDescription' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_description',
            'description' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_explanation',
        ],
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
        'labels' => [
            'title' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf:recordStatistics.title',
            'shortDescription' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf:recordStatistics.shortDescription',
            'description' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf:recordStatistics.description',
        ],
        'routes' => [
            '_default' => [
                'target' => RecordStatisticsController::class . '::handleRequest',
            ],
        ],
    ],
];
