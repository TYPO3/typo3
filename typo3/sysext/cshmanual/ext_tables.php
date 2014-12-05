<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'help',
		'cshmanual',
		'top',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'help_cshmanual',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-cshmanual.png',
				),
				'll_ref' => 'LLL:EXT:lang/locallang_mod_help_cshmanual.xlf',
			),
		)
	);
}
