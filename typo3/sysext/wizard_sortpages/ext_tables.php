<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::insertModuleFunction('web_func', 'TYPO3\\CMS\\WizardSortPages\\View\\SortPagesWizardModuleFunction', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'class.tx_wizardsortpages_webfunc_2.php', 'LLL:EXT:wizard_sortpages/locallang.php:wiz_sort', 'wiz');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_sortpages/locallang_csh.xml');
}
?>