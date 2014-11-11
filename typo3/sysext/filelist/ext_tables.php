<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'file',
		'list',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'file_list',
			'workspaces' => 'online,custom',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-filelist.png',
				),
				'll_ref' => 'LLL:EXT:lang/locallang_mod_file_list.xlf',
			),
		)
	);
}
