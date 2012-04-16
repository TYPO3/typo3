<?php
if (!defined ('TYPO3_MODE'))
	die ('Access denied.');

if (TYPO3_MODE === 'BE') {

		// Module Web->View
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'web',
		'view',
		'after:layout',
		array(
			'View' => 'show',
		),
		array(
			'icon' => 'EXT:viewpage/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);
}
?>