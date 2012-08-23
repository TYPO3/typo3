<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::insertModuleFunction('web_func', 'TYPO3\\CMS\\WizardCreatePages\\Controller\\CreatePagesWizardModuleFunctionController', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'class.tx_wizardcrpages_webfunc_2.php', 'LLL:EXT:wizard_crpages/locallang.php:wiz_crMany', 'wiz');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_crpages/locallang_csh.xml');
}
?>