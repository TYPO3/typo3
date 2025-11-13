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
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Backend\View\SetupModuleViewMode;
use TYPO3\CMS\Backend\View\SetupSettingsViewMode;

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
            'viewMode' => PageViewMode::LayoutView->value,
            'showHidden' => true,
        ],
    ],
    'records' => [
        'parent' => 'content',
        'position' => ['after' => 'web_layout'],
        'access' => 'user',
        'path' => '/module/content/records',
        'iconIdentifier' => 'module-list',
        'labels' => 'backend.modules.list',
        'aliases' => ['web_list'],
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
    'content_status' => [
        'parent' => 'content',
        'position' => ['after' => 'web_FormFormbuilder', 'before' => 'recycler'],
        'access' => 'user',
        'path' => '/module/content/status',
        'iconIdentifier' => 'module-info',
        'labels' => 'backend.modules.status',
        'aliases' => ['web_info'],
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
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
            'detail' => [
                'target' => SiteConfigurationController::class . '::detailAction',
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
            'editSettings' => [
                'target' => SiteSettingsController::class . '::editAction',
            ],
            'saveSettings' => [
                'target' => SiteSettingsController::class . '::saveAction',
                'methods' => ['POST'],
            ],
            'dumpSettings' => [
                'target' => SiteSettingsController::class . '::dumpAction',
                'methods' => ['POST'],
            ],
        ],
        'moduleData' => [
            'viewMode' => SetupModuleViewMode::TILES->value,
            'settingsMode' => SetupSettingsViewMode::BASIC->value,
        ],
    ],
    'link_management' => [
        'parent' => 'site',
        'position' => ['after' => 'site_configuration'],
        'access' => 'user',
        'path' => '/module/link-management',
        'iconIdentifier' => 'module-urls',
        'labels' => 'backend.modules.link_management',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
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
    'content_security_policy' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-security',
        'labels' => 'backend.modules.content_security_policy',
        'aliases' => ['tools_csp'],
        'routes' => [
            '_default' => [
                'target' => CspModuleController::class . '::mainAction',
            ],
        ],
    ],
];
