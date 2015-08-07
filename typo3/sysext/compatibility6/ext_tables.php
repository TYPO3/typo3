<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		\TYPO3\CMS\Compatibility6\Controller\WebFunctionWizardsBaseController::class,
		NULL,
		'LLL:EXT:compatibility6/Resources/Private/Language/wizards.xlf:mod_wizards'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:compatibility6/Resources/Private/Language/wizards_csh.xlf');
}
