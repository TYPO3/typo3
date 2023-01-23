<?php

use TYPO3\CMS\Tstemplate\Controller\ConstantEditorController;
use TYPO3\CMS\Tstemplate\Controller\InfoModifyController;
use TYPO3\CMS\Tstemplate\Controller\ObjectBrowserController;
use TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerController;
use TYPO3\CMS\Tstemplate\Controller\TemplateRecordsOverviewController;

/**
 * Definitions for modules provided by EXT:tstemplate
 */
return [
    'web_ts' => [
        'parent' => 'site',
        'access' => 'admin',
        'path' => '/module/web/ts',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:module.typoscript.title',
            'shortDescription' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:module.typoscript.shortDescription',
            'description' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:module.typoscript.description',
        ],

        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
    ],
    'web_typoscript_recordsoverview' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/records-overview',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:submodules.option.templateRecordsOverview',
        ],
        'routes' => [
            '_default' => [
                'target' => TemplateRecordsOverviewController::class . '::handleRequest',
            ],
        ],
    ],
    'web_typoscript_constanteditor' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/constant-editor',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:submodules.option.constantEditor',
        ],
        'routes' => [
            '_default' => [
                'target' => ConstantEditorController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'selectedTemplatePerPage' => [],
            'selectedCategory' => '',
        ],
    ],
    'web_typoscript_infomodify' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/overview',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:submodules.option.infoModify',
        ],
        'routes' => [
            '_default' => [
                'target' => InfoModifyController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'selectedTemplatePerPage' => [],
        ],
    ],
    'web_typoscript_objectbrowser' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/object-browser',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:submodules.option.objectBrowser',
        ],
        'routes' => [
            '_default' => [
                'target' => ObjectBrowserController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'sortAlphabetically' => true,
            'displayConstantSubstitutions' => true,
            'displayComments' => true,
            'searchValue' => '',
            'selectedTemplatePerPage' => [],
            'constantConditions' => [],
            'setupConditions' => [],
            'constantExpandState' => [],
            'setupExpandState' => [],
        ],
    ],
    'web_typoscript_analyzer' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/analyzer',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:submodules.option.templateAnalyzer',
        ],
        'routes' => [
            '_default' => [
                'target' => TemplateAnalyzerController::class . '::indexAction',
            ],
            'source' => [
                'target' => TemplateAnalyzerController::class . '::sourceAction',
            ],
            'sourceWithIncludes' => [
                'target' => TemplateAnalyzerController::class . '::sourceWithIncludesAction',
            ],
        ],
        'moduleData' => [
            'selectedTemplatePerPage' => [],
            'constantConditions' => [],
            'setupConditions' => [],
        ],
    ],
];
