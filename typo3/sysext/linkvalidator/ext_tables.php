<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// Add module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'TYPO3\\CMS\\Linkvalidator\\Report\\LinkValidatorReport', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('linkvalidator') . 'Classes/Report/LinkValidatorReport.php', 'LLL:EXT:linkvalidator/locallang.xml:mod_linkvalidator');
}
// Initialize Context Sensitive Help (CSH)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('linkvalidator', 'EXT:linkvalidator/modfuncreport/locallang_csh.xml');
?>