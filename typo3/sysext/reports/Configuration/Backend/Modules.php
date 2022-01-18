<?php

use TYPO3\CMS\Reports\Controller\ReportController;

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
        'routes' => [
            '_default' => [
                'target' => ReportController::class . '::handleRequest',
            ],
        ],
    ],
];
