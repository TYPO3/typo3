<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
		'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\TableConfiguration\\DatabaseTreeDataProvider',
		\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::SIGNAL_PostProcessTreeData,
		'TYPO3\\CMS\\Backend\\Security\\CategoryPermissionsAspect',
		'addUserPermissionsToCategoryTreeData'
	);

	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = 'TYPO3\\CMS\\Backend\\Backend\\ToolbarItems\\ClearCacheToolbarItem';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = 'TYPO3\\CMS\\Backend\\Backend\\ToolbarItems\\LiveSearchToolbarItem';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = 'TYPO3\\CMS\\Backend\\Backend\\ToolbarItems\\ShortcutToolbarItem';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = 'TYPO3\\CMS\\Backend\\Backend\\ToolbarItems\\UserToolbarItem';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['frontendEditingController']['default'] = 'TYPO3\\CMS\\Core\\FrontendEditing\\FrontendEditingController';
