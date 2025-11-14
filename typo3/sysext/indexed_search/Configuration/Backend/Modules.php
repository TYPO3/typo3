<?php

use TYPO3\CMS\IndexedSearch\Controller\AdministrationController;

/**
 * Definitions for modules provided by EXT:indexed_search
 */
return [
    'manage_search_index' => [
        'parent' => 'content_status',
        'position' => ['after' => 'web_info_translations'],
        'access' => 'user',
        'iconIdentifier' => 'module-indexed_search',
        'labels' => 'indexed_search.module',
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
