<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// avoid that this block is loaded in the frontend or within the upgrade-wizards
if (TYPO3_MODE == 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	/** Registers a Backend Module */
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'web',
		'workspaces',
		'before:info',
		array(
			// An array holding the controller-action-combinations that are accessible
			'Review' => 'index,fullIndex,singleIndex',
			'Preview' => 'index,newPage'
		),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:workspaces/Resources/Public/Images/moduleicon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml',
			'navigationComponentId' => 'typo3-pagetree'
		)
	);
	// register ExtDirect
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Workspaces.ExtDirect', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/ExtDirect/Server.php:TYPO3\\CMS\\Workspaces\\ExtDirect\\ExtDirectServer', 'web_WorkspacesWorkspaces', 'user,group');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Workspaces.ExtDirectActions', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/ExtDirect/ActionHandler.php:TYPO3\\CMS\\Workspaces\\ExtDirect\\ActionHandler', 'web_WorkspacesWorkspaces', 'user,group');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Workspaces.ExtDirectMassActions', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/ExtDirect/MassActionHandler.php:TYPO3\\CMS\\Workspaces\\ExtDirect\\MassActionHandler', 'web_WorkspacesWorkspaces', 'user,group');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Ajax.ExtDirect.ToolbarMenu', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/ExtDirect/ToolbarMenu.php:TYPO3\\CMS\\Workspaces\\ExtDirect\\ToolbarMenu');
}

// todo move icons to Core sprite or keep them here and remove the todo note ;)
$icons = array(
	'sendtonextstage' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/version-workspace-sendtonextstage.png',
	'sendtoprevstage' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/version-workspace-sendtoprevstage.png',
	'generatepreviewlink' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/generate-ws-preview-link.png'
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $_EXTKEY);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_workspace_stage', 'EXT:workspaces/Resources/Private/Language/locallang_csh_sysws_stage.xml');
?>