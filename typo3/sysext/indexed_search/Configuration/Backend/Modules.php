<?php

use TYPO3\CMS\IndexedSearch\Controller\AdministrationController;

/**
 * Definitions for modules provided by EXT:indexed_search
 */
return [
    'web_IndexedSearchIsearch' => [
        'parent' => 'web',
        'access' => 'user',
        'iconIdentifier' => 'module-indexed_search',
        'labels' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'IndexedSearch',
        'controllerActions' => [
            AdministrationController::class => [
                'statistic', 'index', 'pages', 'externalDocuments', 'statisticDetails', 'deleteIndexedItem', 'saveStopwordsKeywords', 'wordDetail',
            ],
        ],
    ],
];
