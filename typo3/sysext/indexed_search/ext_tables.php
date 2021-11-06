<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\IndexedSearch\Controller\AdministrationController;

defined('TYPO3') or die();

ExtensionUtility::registerModule(
    'IndexedSearch',
    'web',
    'isearch',
    '',
    [
        AdministrationController::class => 'index,pages,externalDocuments,statistic,statisticDetails,deleteIndexedItem,saveStopwordsKeywords,wordDetail',
    ],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'module-indexed_search',
        'labels' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf',
    ]
);

ExtensionManagementUtility::allowTableOnStandardPages('index_config');
ExtensionManagementUtility::addLLrefForTCAdescr('index_config', 'EXT:indexed_search/Resources/Private/Language/locallang_csh_indexcfg.xlf');
