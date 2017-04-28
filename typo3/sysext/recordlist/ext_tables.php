<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'list',
        '',
        '',
        [
            'routeTarget' => \TYPO3\CMS\Recordlist\RecordList::class . '::mainAction',
            'access' => 'user,group',
            'name' => 'web_list',
            'icon' => 'EXT:recordlist/Resources/Public/Icons/module-list.svg',
            'labels' => 'LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf'
        ]
    );

    // register element browsers
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['db'] =  \TYPO3\CMS\Recordlist\Browser\DatabaseBrowser::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['file'] =  \TYPO3\CMS\Recordlist\Browser\FileBrowser::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['folder'] =  \TYPO3\CMS\Recordlist\Browser\FolderBrowser::class;
}
