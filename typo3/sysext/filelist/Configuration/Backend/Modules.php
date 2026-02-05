<?php

use TYPO3\CMS\Filelist\Controller\FileListController;

/**
 * Definitions for modules provided by EXT:filelist
 */
return [
    'media_management' => [
        'parent' => 'media',
        'access' => 'user',
        'path' => '/module/file/list',
        'iconIdentifier' => 'module-file',
        'labels' => 'filelist.module',
        'aliases' => ['file_FilelistList'],
        'routes' => [
            '_default' => [
                'target' => FileListController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'displayThumbs' => true,
            'clipBoard' => true,
            'sortField' => 'name',
            'sortDirection' => \TYPO3\CMS\Filelist\Type\SortDirection::ASCENDING->value,
            'viewMode' => null,
        ],
    ],
];
