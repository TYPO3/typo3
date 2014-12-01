<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Register AJAX calls
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxOpendocs::renderMenu', \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class . '->renderAjax');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxOpendocs::closeDocument', \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class . '->closeDocument');
	// Register update signal to update the number of open documents
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['OpendocsController::updateNumber'] = \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class . '->updateNumberOfOpenDocsHook';
}
