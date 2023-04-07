<?php

use TYPO3\CMS\Backend\Controller\AboutController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigActiveController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigIncludesController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigRecordsOverviewController;
use TYPO3\CMS\Backend\Controller\RecordListController;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;
use TYPO3\CMS\Backend\Security\ContentSecurityPolicy\CspModuleController;

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
        'position' => ['after' => 'page_preview'],
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
            'searchBox' => false,
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
    'about' => [
        'parent' => 'help',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/help/about',
        'iconIdentifier' => 'module-about',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/Modules/about.xlf',
        'aliases' => ['help_AboutAbout'],
        'routes' => [
            '_default' => [
                'target' => AboutController::class . '::handleRequest',
            ],
        ],
    ],
    'pagetsconfig' => [
        'parent' => 'site',
        'access' => 'admin',
        'path' => '/module/pagetsconfig',
        'iconIdentifier' => 'module-tsconfig',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig.title',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig.description',
            'shortDescription' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig.shortDescription',
        ],
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
    ],
    'pagetsconfig_pages' => [
        'parent' => 'pagetsconfig',
        'access' => 'admin',
        'path' => '/module/pagetsconfig/records',
        'iconIdentifier' => 'module-tsconfig',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_pages',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTsConfigRecordsOverviewController::class . '::handleRequest',
            ],
        ],
    ],
    'pagetsconfig_active' => [
        'parent' => 'pagetsconfig',
        'access' => 'admin',
        'path' => '/module/pagetsconfig/active',
        'iconIdentifier' => 'module-tsconfig',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_active',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTsConfigActiveController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'sortAlphabetically' => true,
            'displayComments' => true,
            'displayConstantSubstitutions' => true,
            'pageTsConfigConditions' => [],
        ],
    ],
    'pagetsconfig_includes' => [
        'parent' => 'pagetsconfig',
        'access' => 'admin',
        'path' => '/module/pagetsconfig/includes',
        'iconIdentifier' => 'module-tsconfig',
        'labels' => [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_includes',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTsConfigIncludesController::class . '::indexAction',
            ],
            'source' => [
                'target' => PageTsConfigIncludesController::class . '::sourceAction',
            ],
            'sourceWithIncludes' => [
                'target' => PageTsConfigIncludesController::class . '::sourceWithIncludesAction',
            ],
        ],
        'moduleData' => [
            'pageTsConfigConditions' => [],
        ],
    ],
    'tools_csp' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-security',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/Modules/content-security-policy.xlf',
        'routes' => [
            '_default' => [
                'target' => CspModuleController::class . '::mainAction',
            ],
        ],
    ],
];
