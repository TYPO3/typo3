<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	/**
	 * Registers "Styleguide" backend module
	 */
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'tools',
		'styleguide',
		'',
		array(
			'Styleguide' => 'index, typography, trees, tables, buttons, forms, flashMessages, helpers, icons'
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_styleguide.xlf',
		)
	);
}