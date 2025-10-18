<?php

/**
 * Configuration of the main modules (having no parent and no path)
 */
return [
    'content' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/Modules/content.xlf',
        'iconIdentifier' => 'modulegroup-web',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'aliases' => ['web'],
    ],
    'media' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/Modules/media.xlf',
        'iconIdentifier' => 'modulegroup-file',
        'navigationComponent' => '@typo3/backend/tree/file-storage-tree-container',
        'aliases' => ['file'],
    ],
    'site' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_site.xlf',
        'workspaces' => 'live',
        'iconIdentifier' => 'modulegroup-site',
    ],
    'user' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_usertools.xlf',
        'iconIdentifier' => 'modulegroup-user',
        'workspaces' => '*',
        'appearance' => [
            'renderInModuleMenu' => false,
        ],
    ],
    'tools' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_admintools.xlf',
        'iconIdentifier' => 'modulegroup-tools',
    ],
    'system' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_system.xlf',
        'iconIdentifier' => 'modulegroup-system',
    ],
    'help' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_help.xlf',
        'iconIdentifier' => 'modulegroup-help',
        'appearance' => [
            'renderInModuleMenu' => false,
        ],
    ],
];
