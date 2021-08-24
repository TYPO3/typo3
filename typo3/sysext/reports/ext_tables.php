<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'system',
    'reports',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Reports\Controller\ReportController::class . '::handleRequest',
        'access' => 'admin',
        'name' => 'system_reports',
        'icon' => 'EXT:reports/Resources/Public/Icons/module-reports.svg',
        'labels' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf'
    ]
);
