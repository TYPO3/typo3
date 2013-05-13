<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\TstemplateAnalyzer\\Controller\\TemplateAnalyzerModuleFunctionController',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/TemplateAnalyzerModuleFunctionController.php',
		'LLL:EXT:tstemplate/ts/locallang.xlf:templateAnalyzer'
	);
}
?>