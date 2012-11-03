<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_func', 'TYPO3\\CMS\\WizardCrpages\\Controller\\CreatePagesWizardModuleFunctionController', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'class.tx_wizardcrpages_webfunc_2.php', 'LLL:EXT:wizard_crpages/locallang.php:wiz_crMany', 'wiz');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_crpages/locallang_csh.xml');
}
?>