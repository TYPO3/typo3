<?php

declare(strict_types=1);

use TYPO3\CMS\Belog\Controller\BackendLogController;
use TYPO3\CMS\Belog\Module\BackendLogModuleBootstrap;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// Module Web->Info->Log
ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    BackendLogModuleBootstrap::class,
    '',
    'Log'
);

// Module Tools->Log
ExtensionUtility::registerModule(
    'Belog',
    'system',
    'log',
    '',
    [
        BackendLogController::class => 'list,deleteMessage',
    ],
    [
        'access' => 'admin',
        'iconIdentifier' => 'module-belog',
        'labels' => 'LLL:EXT:belog/Resources/Private/Language/locallang_mod.xlf',
    ]
);
