<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// Add module
	\TYPO3\CMS\Core\Extension\ExtensionManager::insertModuleFunction('web_info', 'TYPO3\\CMS\\Linkvalidator\\Report\\LinkValidatorReport', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('linkvalidator') . 'modfuncreport/class.tx_linkvalidator_modfuncreport.php', 'LLL:EXT:linkvalidator/locallang.xml:mod_linkvalidator');
}
// Initialize Context Sensitive Help (CSH)
\TYPO3\CMS\Core\Extension\ExtensionManager::addLLrefForTCAdescr('linkvalidator', 'EXT:linkvalidator/modfuncreport/locallang_csh.xml');
?>