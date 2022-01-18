<?php

/**
 * Configuration of the main modules (having no parent and no path)
 */
return [
    'web' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web.xlf',
        'iconIdentifier' => 'modulegroup-web',
        'navigationComponent' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
    ],
    'site' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_site.xlf',
        'workspaces' => 'live',
        'iconIdentifier' => 'modulegroup-site',
    ],
    'file' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_file.xlf',
        'iconIdentifier' => 'modulegroup-file',
        'navigationComponent' => 'TYPO3/CMS/Backend/Tree/FileStorageTreeContainer',
    ],
    'user' => [
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_usertools.xlf',
        'iconIdentifier' => 'modulegroup-user',
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
