<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'file',
    'FilelistList',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Filelist\Controller\FileListController::class . '::handleRequest',
        'access' => 'user,group',
        'workspaces' => 'online,custom',
        'name' => 'file_FilelistList',
        'icon' => 'EXT:filelist/Resources/Public/Icons/module-filelist.svg',
        'labels' => 'LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf'
    ]
);
