<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'ts',
		'',
		$extensionPath . 'ts/'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateConstantEditorModuleFunctionController',
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:constantEditor'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController',
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:infoModify'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateObjectBrowserModuleFunctionController',
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:objectBrowser'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\Tstemplate\\Controller\\TemplateAnalyzerModuleFunctionController',
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:templateAnalyzer'
	);

}