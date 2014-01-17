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
		'TYPO3\\CMS\\TsTemplate\\Controller\\TypoScriptTemplateConstantEditorModuleFunctionController',
		$extensionPath . 'Classes/Controller/TypoScriptTemplateConstantEditorModuleFunctionController.php',
		'LLL:EXT:tstemplate/ts/locallang.xlf:constantEditor'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\TsTemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController',
		$extensionPath . 'Classes/Controller/TypoScriptTemplateInformationModuleFunctionController.php',
		'LLL:EXT:tstemplate/ts/locallang.xlf:infoModify'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\TsTemplate\\Controller\\TypoScriptTemplateObjectBrowserModuleFunctionController',
		$extensionPath  . 'Classes/Controller/TypoScriptTemplateObjectBrowserModuleFunctionController.php',
		'LLL:EXT:tstemplate/ts/locallang.xlf:objectBrowser'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'TYPO3\\CMS\\TsTemplate\\Controller\\TemplateAnalyzerModuleFunctionController',
		$extensionPath . 'Classes/Controller/TemplateAnalyzerModuleFunctionController.php',
		'LLL:EXT:tstemplate/ts/locallang.xlf:templateAnalyzer'
	);

}
