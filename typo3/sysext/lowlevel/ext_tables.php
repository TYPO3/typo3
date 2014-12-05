<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'system',
		'dbint',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'dbint/',
		array(
			'script' => '_DISPATCH',
			'access' => 'admin',
			'name' => 'system_dbint',
			'workspaces' => 'online',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-dbint.png',
				),
				'll_ref' => 'LLL:EXT:lowlevel/dbint/locallang_mod.xlf',
			),
		)
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'system',
		'config',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'config/',
		array(
			'script' => '_DISPATCH',
			'access' => 'admin',
			'name' => 'system_config',
			'workspaces' => 'online',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-config.png',
				),
				'll_ref' => 'LLL:EXT:lowlevel/config/locallang_mod.xlf',
			),
		)
	);
}
