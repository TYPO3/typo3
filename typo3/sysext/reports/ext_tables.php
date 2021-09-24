<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Reports\Controller\ReportController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'system',
    'reports',
    '',
    '',
    [
        'routeTarget' => ReportController::class . '::handleRequest',
        'access' => 'admin',
        'name' => 'system_reports',
        'icon' => 'EXT:reports/Resources/Public/Icons/module-reports.svg',
        'labels' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf',
    ]
);
