<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Reports',
    'system',
    'txreportsM1',
    '',
    [
        'Report' => 'index,detail'
    ],
    [
        'access' => 'admin',
        'icon' => 'EXT:reports/Resources/Public/Icons/module-reports.svg',
        'labels' => 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf'
    ]
);
