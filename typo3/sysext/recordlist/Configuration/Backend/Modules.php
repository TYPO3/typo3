<?php

use TYPO3\CMS\Recordlist\Controller\RecordListController;

/**
 * Definitions for modules provided by EXT:recordlist
 */
return [
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
];
