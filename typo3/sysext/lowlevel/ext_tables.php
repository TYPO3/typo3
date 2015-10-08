<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'system',
        'dbint',
        '',
        '',
        array(
            'routeTarget' => \TYPO3\CMS\Lowlevel\View\DatabaseIntegrityView::class . '::mainAction',
            'access' => 'admin',
            'name' => 'system_dbint',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:lowlevel/Resources/Public/Icons/module-dbint.svg',
                ),
                'll_ref' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf',
            ),
        )
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'system',
        'config',
        '',
        '',
        array(
            'routeTarget' => \TYPO3\CMS\Lowlevel\View\ConfigurationView::class . '::mainAction',
            'access' => 'admin',
            'name' => 'system_config',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:lowlevel/Resources/Public/Icons/module-config.svg',
                ),
                'll_ref' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod_configuration.xlf',
            ),
        )
    );
}
