<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Filelist',
    'file',
    'list',
    '',
    [
        'FileList' => 'index, search',
    ],
    [
        'access' => 'user,group',
        'workspaces' => 'online,custom',
        'icon' => 'EXT:filelist/Resources/Public/Icons/module-filelist.svg',
        'labels' => 'LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf'
    ]
);
