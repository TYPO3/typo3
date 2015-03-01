<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Register record history module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'record_history',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/RecordHistory/'
	);

	// Register login frameset
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'login_frameset',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/LoginFrameset/'
	);

	// Register logout
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'logout',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Logout/'
	);

	// Register file_edit
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'file_edit',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/File/Edit/'
	);

	// Register file_newfolder
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'file_newfolder',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/File/Newfolder/'
	);

	// Register file_rename
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'file_rename',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/File/Rename/'
	);

	// Register file_rename
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'file_upload',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/File/Upload/'
	);

	// Register tce_db
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tce_db',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/File/Database/'
	);

	// Register tce_file
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tce_file',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/File/Administration/'
	);

	// Register edit wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_edit',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/EditWizard/'
	);

	// Register add wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_add',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/AddWizard/'
	);

	// Register list wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_list',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/ListWizard/'
	);

	// Register table wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_table',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/TableWizard/'
	);

	// Register rte wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_rte',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/RteWizard/'
	);

	// Register colorpicker wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_colorpicker',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/ColorpickerWizard/'
	);

	// Register backend_layout wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_backend_layout',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/BackendLayoutWizard/'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'layout',
		'top',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Layout/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'web_layout',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../../Resources/Public/Icons/module-page.png',
				),
				'll_ref' => 'LLL:EXT:cms/layout/locallang_mod.xlf',
			),
		)
	);

	// Register new content element module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'new_content_element',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/NewContentElement/'
	);

	// Register move element module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'move_element',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/MoveElement/'
	);

	// Register show item module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'show_item',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/ShowItem/'
	);

	// Register browser
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'browser',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Browser/'
	);
}
