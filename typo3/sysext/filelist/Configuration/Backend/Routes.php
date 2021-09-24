<?php

/**
 * Definitions for routes provided by EXT:backend
 * Contains all "regular" routes for entry points
 *
 * Please note that this setup is preliminary until all core use-cases are set up here.
 * Especially some more properties regarding modules will be added until TYPO3 CMS 7 LTS, and might change.
 *
 * Currently the "access" property is only used so no token creation + validation is made,
 * but will be extended further.
 */
return [

    // Editing the contents of a file
    'file_edit' => [
        'path' => '/file/editcontent',
        'target' => \TYPO3\CMS\Filelist\Controller\File\EditFileController::class . '::mainAction',
    ],

    // Create a new folder
    'file_newfolder' => [
        'path' => '/file/new',
        'target' => \TYPO3\CMS\Filelist\Controller\File\CreateFolderController::class . '::mainAction',
    ],

    // Rename a file
    'file_rename' => [
        'path' => '/file/rename',
        'target' => \TYPO3\CMS\Filelist\Controller\File\RenameFileController::class . '::mainAction',
    ],

    // Replace a file with a different one
    'file_replace' => [
        'path' => '/file/replace',
        'target' => \TYPO3\CMS\Filelist\Controller\File\ReplaceFileController::class . '::mainAction',
    ],

    // Upload new files
    'file_upload' => [
        'path' => '/file/upload',
        'target' => \TYPO3\CMS\Filelist\Controller\File\FileUploadController::class . '::mainAction',
    ],

    'file_download' => [
        'path' => '/file/download',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Filelist\Controller\FileDownloadController::class . '::handleRequest',
    ],
];
