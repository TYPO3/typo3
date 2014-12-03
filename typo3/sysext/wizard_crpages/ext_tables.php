<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		\TYPO3\CMS\WizardCrpages\Controller\CreatePagesWizardModuleFunctionController::class,
		NULL,
		'LLL:EXT:wizard_crpages/locallang.xlf:wiz_crMany'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
		'_MOD_web_func',
		'EXT:wizard_crpages/locallang_csh.xlf'
	);
}
