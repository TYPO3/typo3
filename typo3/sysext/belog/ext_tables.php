<?php
defined('TYPO3_MODE') or die();

// Module Web->Info->Log
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    \TYPO3\CMS\Belog\Module\BackendLogModuleBootstrap::class,
    null,
    'Log'
);

// Module Tools->Log
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Belog',
    'system',
    'log',
    '',
    [
        'BackendLog' => 'list,deleteMessage',
    ],
    [
        'access' => 'admin',
        'icon' => 'EXT:belog/Resources/Public/Icons/module-belog.svg',
        'labels' => 'LLL:EXT:belog/Resources/Private/Language/locallang_mod.xlf',
    ]
);
