<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Filelist\Controller\FileListController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'file',
    'FilelistList',
    '',
    '',
    [
        'routeTarget' => FileListController::class . '::handleRequest',
        'access' => 'user,group',
        'workspaces' => 'online,custom',
        'name' => 'file_FilelistList',
        'icon' => 'EXT:filelist/Resources/Public/Icons/module-filelist.svg',
        'labels' => 'LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf',
    ]
);
