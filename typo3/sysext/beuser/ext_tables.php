<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	// Module Admin > Backend Users
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'system',
		'tx_Beuser',
		'top',
		array(
			'BackendUser' => 'index, addToCompareList, removeFromCompareList, compare, online, terminateBackendUserSession'
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf'
		)
	);
}
