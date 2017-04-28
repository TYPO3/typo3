<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'tools',
    'toolsmaintenance',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::maintenanceAction',
        'access' => 'systemMaintainer',
        'name' => 'tools_toolsmaintenance',
        'iconIdentifier' => 'module-install-maintenance',
        'labels' => 'LLL:EXT:install/Resources/Private/Language/ModuleInstallMaintenance.xlf'
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'tools',
    'toolssettings',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::settingsAction',
        'access' => 'systemMaintainer',
        'name' => 'tools_toolssettings',
        'iconIdentifier' => 'module-install-settings',
        'labels' => 'LLL:EXT:install/Resources/Private/Language/ModuleInstallSettings.xlf'
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'tools',
    'toolsupgrade',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::upgradeAction',
        'access' => 'systemMaintainer',
        'name' => 'tools_toolsupgrade',
        'iconIdentifier' => 'module-install-upgrade',
        'labels' => 'LLL:EXT:install/Resources/Private/Language/ModuleInstallUpgrade.xlf'
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'tools',
    'toolsenvironment',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::environmentAction',
        'access' => 'systemMaintainer',
        'name' => 'tools_toolsenvironment',
        'iconIdentifier' => 'module-install-environment',
        'labels' => 'LLL:EXT:install/Resources/Private/Language/ModuleInstallEnvironment.xlf'
    ]
);
