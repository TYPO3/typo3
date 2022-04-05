<?php

use TYPO3\CMS\Backend\Controller\AboutController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
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
    'site_configuration' => [
        'parent' => 'site',
        'position' => ['before' => '*'],
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
];
