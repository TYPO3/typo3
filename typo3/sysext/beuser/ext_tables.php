<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	// Module Admin > Backend Users
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule($_EXTKEY, 'tools', 'tx_Beuser', 'top', array(
		'BackendUser' => 'index, addToCompareList, removeFromCompareList, compare, online, terminateBackendUserSession'
	), array(
		'access' => 'user,group',
		'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-24x24.png',
		'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf'
	));
}
?>