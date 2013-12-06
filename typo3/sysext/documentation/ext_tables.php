<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	// Registers a Backend Module
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'help',
		'documentation',
		'top',
		array(
			'Document' => 'list, download, fetch',
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);
}
