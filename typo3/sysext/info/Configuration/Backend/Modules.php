<?php

use TYPO3\CMS\Info\Controller\InfoModuleController;
use TYPO3\CMS\Info\Controller\PageInformationController;
use TYPO3\CMS\Info\Controller\TranslationStatusController;

/**
 * Definitions for modules provided by EXT:info
 */
return [
    'web_info' => [
        'parent' => 'web',
        'access' => 'user',
        'path' => '/module/web/info',
        'iconIdentifier' => 'module-info',
        'labels' => 'LLL:EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => InfoModuleController::class . '::handleRequest',
            ],
        ],
    ],
    'web_info_overview' => [
        'parent' => 'web_info',
        'access' => 'user',
        'path' => '/module/web/info/overview',
        'iconIdentifier' => 'module-info',
        'labels' => [
            'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page',
        ],
        'routes' => [
            '_default' => [
                'target' => PageInformationController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'pages' => '0',
            'depth' => 0,
        ],
    ],
    'web_info_translations' => [
        'parent' => 'web_info',
        'access' => 'user',
        'path' => '/module/web/info/translations',
        'iconIdentifier' => 'module-info',
        'labels' => [
            'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_lang',
        ],
        'routes' => [
            '_default' => [
                'target' => TranslationStatusController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'depth' => 0,
            'lang' => 0,
        ],
    ],
];
