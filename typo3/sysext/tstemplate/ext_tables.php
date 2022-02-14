<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerController;
use TYPO3\CMS\Tstemplate\Controller\TyposcriptConstantEditorController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptObjectBrowserController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationController;

defined('TYPO3') or die();

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TyposcriptConstantEditorController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:constantEditor'
);

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TypoScriptTemplateInformationController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:infoModify'
);

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TypoScriptObjectBrowserController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:objectBrowser'
);

ExtensionManagementUtility::insertModuleFunction(
    'web_ts',
    TemplateAnalyzerController::class,
    '',
    'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:templateAnalyzer'
);
