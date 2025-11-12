<?php

use TYPO3\CMS\Backend\Controller\AboutController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigActiveController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigIncludesController;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigRecordsOverviewController;
use TYPO3\CMS\Backend\Controller\RecordListController;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;
use TYPO3\CMS\Backend\Controller\SiteSettingsController;
use TYPO3\CMS\Backend\Security\ContentSecurityPolicy\CspModuleController;

/**
 * Definitions for modules provided by EXT:backend
 */
return [
    'web_layout' => [
        'parent' => 'content',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/web/layout',
        'iconIdentifier' => 'module-page',
        'labels' => 'backend.modules.layout',
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
        'parent' => 'content',
        'position' => ['after' => 'web_layout'],
        'access' => 'user',
        'path' => '/module/web/list',
        'iconIdentifier' => 'module-list',
        'labels' => 'backend.modules.list',
        'routes' => [
            '_default' => [
                'target' => RecordListController::class . '::mainAction',
            ],
        ],
        'moduleData' => [
            'clipBoard' => true,
            'searchBox' => false,
            'collapsedTables' => [],
            'language' => -1,
        ],
    ],
    'site_configuration' => [
        'parent' => 'site',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'path' => '/module/site/configuration',
        'iconIdentifier' => 'module-sites',
        'labels' => 'backend.modules.site_configuration',
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
    'site_settings' => [
        'parent' => 'site',
        'position' => ['after' => 'site_configuration'],
        // @todo implement access=user
        'access' => 'admin',
        'path' => '/module/site/settings',
        'iconIdentifier' => 'module-site-settings',
        'labels' => 'backend.modules.site_settings',
        'routes' => [
            '_default' => [
                'target' => SiteSettingsController::class . '::overviewAction',
            ],
            'edit' => [
                'target' => SiteSettingsController::class . '::editAction',
            ],
            'save' => [
                'target' => SiteSettingsController::class . '::saveAction',
                'methods' => ['POST'],
            ],
            'dump' => [
                'target' => SiteSettingsController::class . '::dumpAction',
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
        'labels' => 'backend.modules.about',
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
        'labels' => 'backend.modules.pagetsconfig',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
    ],
    'pagetsconfig_pages' => [
        'parent' => 'pagetsconfig',
        'access' => 'admin',
        'path' => '/module/pagetsconfig/records',
        'iconIdentifier' => 'module-tsconfig',
        'labels' => 'backend.modules.pagetsconfig_pages',
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
        'labels' => 'backend.modules.pagetsconfig_active',
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
        'labels' => 'backend.modules.pagetsconfig_includes',
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
    ],
    'tools_csp' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-security',
        'labels' => 'backend.modules.content_security_policy',
        'routes' => [
            '_default' => [
                'target' => CspModuleController::class . '::mainAction',
            ],
        ],
    ],
];
