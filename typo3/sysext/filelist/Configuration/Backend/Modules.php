<?php

use TYPO3\CMS\Filelist\Controller\FileListController;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * Definitions for modules provided by EXT:filelist
 */
return [
    'media_management' => [
        'parent' => 'file',
        'access' => 'user',
        'path' => '/module/file/list',
        'iconIdentifier' => 'module-filelist',
        'labels' => 'LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf',
        'aliases' => ['file_FilelistList'],
        'routes' => [
            '_default' => [
                'target' => FileListController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'displayThumbs' => true,
            'clipBoard' => true,
            'sort' => 'file',
            'reverse' => false,
            'viewMode' => ViewMode::TILES->value,
        ],
    ],
];
