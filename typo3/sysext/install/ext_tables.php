<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    // Register report module additions
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['typo3'][] = \TYPO3\CMS\Install\Report\InstallStatusReport::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['security'][] = \TYPO3\CMS\Install\Report\SecurityStatusReport::class;

    // Only add the environment status report if not in CLI mode
    if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['system'][] = \TYPO3\CMS\Install\Report\EnvironmentStatusReport::class;
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tools',
        'toolsmaintenance',
        '',
        '',
        [
            'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::index',
            'routeParameters' => [
                'install' => [
                    'action' => 'maintenance'
                ]
            ],
            'access' => 'admin',
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
            'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::index',
            'routeParameters' => [
                'install' => [
                    'action' => 'settings'
                ]
            ],
            'access' => 'admin',
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
            'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::index',
            'routeParameters' => [
                'install' => [
                    'action' => 'upgrade'
                ]
            ],
            'access' => 'admin',
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
            'routeTarget' => \TYPO3\CMS\Install\Controller\BackendModuleController::class . '::index',
            'routeParameters' => [
                'install' => [
                    'action' => 'environment'
                ]
            ],
            'access' => 'admin',
            'name' => 'tools_toolsenvironment',
            'iconIdentifier' => 'module-install-environment',
            'labels' => 'LLL:EXT:install/Resources/Private/Language/ModuleInstallEnvironment.xlf'
        ]
    );
}
