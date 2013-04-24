<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'web',
		'Recycler',
		'',
		array(
			'RecyclerModule' => 'index',
		),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:recycler/Resources/Public/Icons/module-recycler.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);
}