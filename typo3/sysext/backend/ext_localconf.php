<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
		\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::class,
		\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::SIGNAL_PostProcessTreeData,
		\TYPO3\CMS\Backend\Security\CategoryPermissionsAspect::class,
		'addUserPermissionsToCategoryTreeData'
	);

	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \TYPO3\CMS\Backend\Backend\ToolbarItems\ClearCacheToolbarItem::class;
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \TYPO3\CMS\Backend\Backend\ToolbarItems\HelpToolbarItem::class;
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \TYPO3\CMS\Backend\Backend\ToolbarItems\LiveSearchToolbarItem::class;
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem::class;
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class;
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \TYPO3\CMS\Backend\Backend\ToolbarItems\UserToolbarItem::class;

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416747] = array(
		'provider' => \TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::class,
		'sorting' => 50,
		'icon-class' => 'fa-key',
		'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:login.link'
	);
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['frontendEditingController']['default'] = \TYPO3\CMS\Core\FrontendEditing\FrontendEditingController::class;
