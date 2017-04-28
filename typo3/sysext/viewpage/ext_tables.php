<?php
defined('TYPO3_MODE') or die();

// Module Web->View
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Viewpage',
    'web',
    'view',
    'after:layout',
    [
        'ViewModule' => 'show'
    ],
    [
        'icon' => 'EXT:viewpage/Resources/Public/Icons/module-viewpage.svg',
        'labels' => 'LLL:EXT:viewpage/Resources/Private/Language/locallang_mod.xlf',
        'access' => 'user,group'
    ]
);
