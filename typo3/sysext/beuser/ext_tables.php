<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {

		// Module Admin > Backend Users
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'tools',
		'tx_Beuser',
		'top',
		array(
			'BackendUser' => 'index, addToCompareList, removeFromCompareList, compare, online, terminateBackendUserSession',
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);

}

?>