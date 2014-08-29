<?php
defined('TYPO3_MODE') or die();

// Avoid that this block is loaded in frontend or within upgrade wizards
if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'help',
		'about',
		'top',
		array('About' => 'index'),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:about/Resources/Public/Icons/module-about.gif',
			'labels' => 'LLL:EXT:lang/locallang_mod_help_about.xlf'
		)
	);
}
