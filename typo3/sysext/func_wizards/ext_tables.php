<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::insertModuleFunction('web_func', 'TYPO3\\CMS\\FuncWizards\\Controller\\WebFunctionWizardsBaseController', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'class.tx_funcwizards_webfunc.php', 'LLL:EXT:func_wizards/locallang.php:mod_wizards');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addLLrefForTCAdescr('_MOD_web_func', 'EXT:func_wizards/locallang_csh.xml');
}
?>