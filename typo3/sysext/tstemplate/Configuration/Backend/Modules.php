<?php

use TYPO3\CMS\Tstemplate\Controller\ConstantEditorController;
use TYPO3\CMS\Tstemplate\Controller\DummyController;
use TYPO3\CMS\Tstemplate\Controller\InfoModifyController;
use TYPO3\CMS\Tstemplate\Controller\ObjectBrowserController;
use TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerController;
use TYPO3\CMS\Tstemplate\Controller\TemplateRecordsOverviewController;

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
                'target' => DummyController::class . '::handleRequest',
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
                'target' => ConstantEditorController::class . '::handleRequest',
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
                'target' => InfoModifyController::class . '::handleRequest',
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
                'target' => ObjectBrowserController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'sortAlphabetically' => true,
            'displayConstantSubstitutions' => true,
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
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:templateAnalyzer',
        ],
        'routes' => [
            '_default' => [
                'target' => TemplateAnalyzerController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'restrictIncludesToMatchingConditions' => false,
            'selectedTemplatePerPage' => [],
            'constantConditions' => [],
            'setupConditions' => [],
        ],
    ],
    'web_typoscript_recordsoverview' => [
        'parent' => 'web_ts',
        'access' => 'admin',
        'path' => '/module/web/typoscript/records-overview',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => [
            'title' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:templateRecordsOverview',
        ],
        'routes' => [
            '_default' => [
                'target' => TemplateRecordsOverviewController::class . '::handleRequest',
            ],
        ],
    ],
];
