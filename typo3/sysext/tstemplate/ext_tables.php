<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'ts',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'web_ts',
        'icon' => 'EXT:tstemplate/Resources/Public/Icons/module-tstemplate.svg',
        'labels' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang_mod.xlf'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    \TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController::class,
    null,
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:constantEditor'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    \TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController::class,
    null,
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:infoModify'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    \TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController::class,
    null,
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:objectBrowser'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    \TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController::class,
    null,
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:templateAnalyzer'
);
