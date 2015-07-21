<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'system',
		'dbint',
		'',
		'EXT:lowlevel/Modules/DatabaseIntegrity/',
		array(
			'script' => '_DISPATCH',
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
		'EXT:lowlevel/Modules/Configuration/',
		array(
			'script' => '_DISPATCH',
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
