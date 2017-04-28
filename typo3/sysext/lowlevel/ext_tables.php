<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'system',
    'dbint',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'system_dbint',
        'workspaces' => 'online',
        'icon' => 'EXT:lowlevel/Resources/Public/Icons/module-dbint.svg',
        'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf'
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'system',
    'config',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Lowlevel\Controller\ConfigurationController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'system_config',
        'workspaces' => 'online',
        'icon' => 'EXT:lowlevel/Resources/Public/Icons/module-config.svg',
        'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod_configuration.xlf'
    ]
);
