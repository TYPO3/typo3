<?php

use TYPO3\CMS\Filelist\Controller\FileListController;

/**
 * Definitions for modules provided by EXT:filelist
 */
return [
    'file_FilelistList' => [
        'parent' => 'file',
        'access' => 'user',
        'path' => '/module/file/list',
        'iconIdentifier' => 'module-filelist',
        'labels' => 'LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf',
        'routes' => [
            '_default' => [
                'target' => FileListController::class . '::handleRequest',
            ],
        ],
    ],
];
