<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'TYPO3\\CMS\\WizardSortpages\\View\\SortPagesWizardModuleFunction',
		NULL,
		'LLL:EXT:wizard_sortpages/locallang.xlf:wiz_sort',
		'wiz'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
		'_MOD_web_func',
		'EXT:wizard_sortpages/locallang_csh.xlf'
	);
}
