<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Add module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'web_txrecyclerM1',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'txrecyclerM1',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'web_txrecyclerM1',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-recycler.png',
				),
				'll_ref' => 'LLL:EXT:recycler/mod1/locallang_mod.xlf',
			),
		)
	);
}
