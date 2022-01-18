<?php

use TYPO3\CMS\Backend\Controller\AboutController;
use TYPO3\CMS\Backend\Controller\HelpController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;

/**
 * Definitions for modules provided by EXT:backend
 */
return [
    'web_layout' => [
        'parent' => 'web',
        'position' => ['top'],
        'access' => 'user',
        'path' => '/module/web/layout',
        'iconIdentifier' => 'module-page',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => PageLayoutController::class . '::mainAction',
            ],
        ],
    ],
    'site_configuration' => [
        'parent' => 'site',
        'position' => ['top'],
        'access' => 'admin',
        'path' => '/module/site/configuration',
        'iconIdentifier' => 'module-sites',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf',
        'routes' => [
            '_default' => [
                'target' => SiteConfigurationController::class . '::handleRequest',
            ],
        ],
    ],
    'help_AboutAbout' => [
        'parent' => 'help',
        'position' => ['top'],
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
    'help_cshmanual' => [
        'parent' => 'help',
        'position' => ['after' => 'help_AboutAbout'],
        'access' => 'user',
        'path' => '/module/help/cshmanual',
        'iconIdentifier' => 'module-cshmanual',
        'labels' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod_help_cshmanual.xlf',
        'routes' => [
            '_default' => [
                'target' => HelpController::class . '::handleRequest',
            ],
        ],
    ],
];
