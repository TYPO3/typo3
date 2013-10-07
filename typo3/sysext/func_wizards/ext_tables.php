<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'TYPO3\\CMS\\FuncWizards\\Controller\\WebFunctionWizardsBaseController',
		NULL,
		'LLL:EXT:func_wizards/locallang.xlf:mod_wizards'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:func_wizards/locallang_csh.xlf');
}
