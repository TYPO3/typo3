<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'tools',
		'txdbalM1',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/',
		array(
			'script' => '_DISPATCH',
			'access' => 'admin',
			'name' => 'tools_txdbalM1',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-dbal.png',
				),
				'll_ref' => 'LLL:EXT:dbal/mod1/locallang_mod.xlf',
			),
		)
	);
}
