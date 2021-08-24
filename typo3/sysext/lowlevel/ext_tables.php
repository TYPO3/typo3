<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Lowlevel\Controller\ConfigurationController;
use TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'system',
    'dbint',
    '',
    '',
    [
        'routeTarget' => DatabaseIntegrityController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'system_dbint',
        'workspaces' => 'online',
        'icon' => 'EXT:lowlevel/Resources/Public/Icons/module-dbint.svg',
        'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf'
    ]
);
ExtensionManagementUtility::addModule(
    'system',
    'config',
    '',
    '',
    [
        'routeTarget' => ConfigurationController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'system_config',
        'workspaces' => 'online',
        'icon' => 'EXT:lowlevel/Resources/Public/Icons/module-config.svg',
        'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod_configuration.xlf'
    ]
);
