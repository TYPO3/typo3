<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'ts',
		'',
		$extensionPath . 'ts/',
		array(
			'script' => '_DISPATCH',
			'access' => 'admin',
			'name' => 'web_ts',
			'labels' => array(
				'tabs_images' => array(
					'tab' => '../Resources/Public/Icons/module-tstemplate.png',
				),
				'll_ref' => 'LLL:EXT:tstemplate/ts/locallang_mod.xlf',
			),
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		\TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController::class,
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:constantEditor'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		\TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController::class,
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:infoModify'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		\TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController::class,
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:objectBrowser'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		\TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController::class,
		NULL,
		'LLL:EXT:tstemplate/ts/locallang.xlf:templateAnalyzer'
	);

}