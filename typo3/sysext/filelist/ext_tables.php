<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
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
            'labels' => 'LLL:EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf'
        ]
    );

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418731] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418732] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FilemountsProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418733] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileStorageProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418734] = \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileDragProvider::class;
}
