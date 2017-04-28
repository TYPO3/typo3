<?php
defined('TYPO3_MODE') or die();

// Register the backend module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Lang',
    'tools',
    'language',
    'after:extensionmanager',
    [
        'Language' => 'listLanguages, listTranslations, getTranslations, updateLanguage, updateTranslation, activateLanguage, deactivateLanguage, removeLanguage',
    ],
    [
        'access' => 'systemMaintainer',
        'icon' => 'EXT:lang/Resources/Public/Icons/module-lang.svg',
        'labels' => 'LLL:EXT:lang/Resources/Private/Language/locallang_mod.xlf',
    ]
);
