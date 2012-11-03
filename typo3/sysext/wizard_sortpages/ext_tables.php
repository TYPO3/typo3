<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_func', 'TYPO3\\CMS\\WizardSortPages\\View\\SortPagesWizardModuleFunction', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'class.tx_wizardsortpages_webfunc_2.php', 'LLL:EXT:wizard_sortpages/locallang.php:wiz_sort', 'wiz');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_sortpages/locallang_csh.xml');
}
?>