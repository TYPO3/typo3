<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'TYPO3\\CMS\\Wizards\\Controller\\CreatePagesWizardModuleFunctionController',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/CreatePagesWizardModuleFunctionController.php',
		'LLL:EXT:wizards/Resources/Private/Language/locallang.xlf:wiz_crMany',
		'wiz'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'TYPO3\\CMS\\Wizards\\Controller\\SortPagesWizardModuleFunctionController',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/SortPagesWizardModuleFunctionController.php',
		'LLL:EXT:wizards/Resources/Private/Language/locallang.xlf:wiz_sort',
		'wiz'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
		'_MOD_web_func',
		'EXT:wizards/Resources/Private/Language/locallang_csh.xlf'
	);
}
?>