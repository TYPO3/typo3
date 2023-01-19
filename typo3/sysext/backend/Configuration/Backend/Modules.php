<?php

use TYPO3\CMS\Backend\Controller\AboutController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigActiveController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigIncludesController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigRecordsOverviewController;
use TYPO3\CMS\Backend\Controller\RecordListController;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;

/**
 * Definitions for modules provided by EXT:backend
 */
return [
    'web_layout' => [
        'parent' => 'web',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/web/layout',
        'iconIdentifier' => 'module-page',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => PageLayoutController::class . '::mainAction',
            ],
        ],
        'moduleData' => [
            'function' => 1,
            'language' => 0,
            'showHidden' => true,
        ],
    ],
    'web_list' => [
        'parent' => 'web',
        'position' => ['after' => 'web_ViewpageView'],
        'access' => 'user',
        'path' => '/module/web/list',
        'iconIdentifier' => 'module-list',
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf',
        'routes' => [
            '_default' => [
                'target' => RecordListController::class . '::mainAction',
            ],
        ],
        'moduleData' => [
            'clipBoard' => true,
            'collapsedTables' => [],
        ],
    ],
    'site_configuration' => [
        'parent' => 'site',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'path' => '/module/site/configuration',
        'iconIdentifier' => 'module-sites',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf',
        'routes' => [
            '_default' => [
                'target' => SiteConfigurationController::class . '::overviewAction',
            ],
            'edit' => [
                'target' => SiteConfigurationController::class . '::editAction',
            ],
            'save' => [
                'target' => SiteConfigurationController::class . '::saveAction',
                'methods' => ['POST'],
            ],
            'delete' => [
                'target' => SiteConfigurationController::class . '::deleteAction',
                'methods' => ['POST'],
            ],
        ],
    ],
    'help_AboutAbout' => [
        'parent' => 'help',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/help/about',
        'iconIdentifier' => 'module-about',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/Modules/about.xlf',
        'routes' => [
            '_default' => [
                'target' => AboutController::class . '::handleRequest',
            ],
        ],
    ],
    'pagetsconfig' => [
        'parent' => 'site',
        'access' => 'user',
        'path' => '/module/pagetsconfig',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig.title',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig.description',
            'shortDescription' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig.shortDescription',
        ],
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
    ],
    'pagetsconfig_records' => [
        'parent' => 'pagetsconfig',
        'access' => 'user',
        'path' => '/module/pagetsconfig/records',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_records',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTsConfigRecordsOverviewController::class . '::handleRequest',
            ],
        ],
    ],
    'pagetsconfig_active' => [
        'parent' => 'pagetsconfig',
        'access' => 'user',
        'path' => '/module/pagetsconfig/active',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_active',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTsConfigActiveController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'tsconf_alphaSort' => false,
        ],
    ],
    'pagetsconfig_includes' => [
        'parent' => 'pagetsconfig',
        'access' => 'user',
        'path' => '/module/pagetsconfig/includes',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_includes',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTsConfigIncludesController::class . '::handleRequest',
            ],
        ],
    ],
];
