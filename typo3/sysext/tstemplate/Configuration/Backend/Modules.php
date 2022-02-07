<?php

use TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptConstantEditorController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptObjectBrowserController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;

/**
 * Definitions for modules provided by EXT:tstemplate
 */
return [
    'web_ts' => [
        'parent' => 'web',
        'access' => 'admin',
        'path' => '/module/web/ts',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang_mod.xlf',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => TypoScriptTemplateModuleController::class . '::handleRequest',
            ],
        ],
    ],
    'web_typoscript_constanteditor' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/constant-editor',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:constantEditor',
        ],
        'routes' => [
            '_default' => [
                'target' => TypoScriptConstantEditorController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'templatesOnPage' => 0,
            'constant_editor_cat' => '',
        ],
    ],
    'web_typoscript_overview' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/overview',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:infoModify',
        ],
        'routes' => [
            '_default' => [
                'target' => TypoScriptTemplateInformationController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'templatesOnPage' => 0,
        ],
    ],
    'web_typoscript_objectbrowser' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/object-browser',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:objectBrowser',
        ],
        'routes' => [
            '_default' => [
                'target' => TypoScriptObjectBrowserController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'templatesOnPage' => 0,
            'ts_browser_type' => 'const',
            'ts_browser_const' => '0',
            'ts_browser_toplevel_setup' => '0',
            'ts_browser_toplevel_const' => '0',
            'ts_browser_alphaSort' => false,
            'ts_browser_regexsearch' => false,
            'ts_browser_showComments' => true,
        ],
    ],
    'web_typoscript_analyzer' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/analyzer',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:templateAnalyzer',
        ],
        'routes' => [
            '_default' => [
                'target' => TemplateAnalyzerController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'templatesOnPage' => 0,
        ],
    ],
];
