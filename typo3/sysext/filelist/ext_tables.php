<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'file',
		'list',
		'',
		'EXT:filelist/Modules/Filelist/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'file_list',
			'workspaces' => 'online,custom',
			'labels' => array(
				'tabs_images' => array(
					'tab' => 'EXT:filelist/Resources/Public/Icons/module-filelist.svg',
				),
				'll_ref' => 'LLL:EXT:lang/locallang_mod_file_list.xlf',
			),
		)
	);
}
