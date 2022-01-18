<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController;

defined('TYPO3') or die();

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TypoScriptTemplateConstantEditorModuleFunctionController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:constantEditor'
);

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TypoScriptTemplateInformationModuleFunctionController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:infoModify'
);

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TypoScriptTemplateObjectBrowserModuleFunctionController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:objectBrowser'
);

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TemplateAnalyzerModuleFunctionController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:templateAnalyzer'
);
