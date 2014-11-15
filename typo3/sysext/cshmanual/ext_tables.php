<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.Cshmanual',
		'help',
		'cshmanual',
		'top',
		array(
			'Help' => 'index,all,detail',
		),
		array(
			'access' => 'user',
			'icon' => 'EXT:cshmanual/Resources/Public/Icons/module-cshmanual.svg',
			'labels' => 'LLL:EXT:lang/locallang_mod_help_cshmanual.xlf',
		)
	);
}