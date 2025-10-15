<?php

use TYPO3\CMS\IndexedSearch\Controller\AdministrationController;

/**
 * Definitions for modules provided by EXT:indexed_search
 */
return [
    'manage_search_index' => [
        'parent' => 'content',
        'access' => 'user',
        'iconIdentifier' => 'module-indexed_search',
        'labels' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf',
        'path' => 'module/manage/search-index',
        'aliases' => ['web_IndexedSearchIsearch'],
        'extensionName' => 'IndexedSearch',
        'controllerActions' => [
            AdministrationController::class => [
                'statistic', 'index', 'pages', 'externalDocuments', 'statisticDetails', 'deleteIndexedItem', 'saveStopwords', 'wordDetail',
            ],
        ],
    ],
];
