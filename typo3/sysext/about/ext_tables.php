<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.About',
    'help',
    'about',
    'top',
    [
        'About' => 'index'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:about/Resources/Public/Icons/module-about.svg',
        'labels' => 'LLL:EXT:about/Resources/Private/Language/Modules/about.xlf'
    ]
);
