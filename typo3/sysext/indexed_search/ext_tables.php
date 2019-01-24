<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'IndexedSearch',
    'web',
    'isearch',
    '',
    [
        \TYPO3\CMS\IndexedSearch\Controller\AdministrationController::class => 'index,pages,externalDocuments,statistic,statisticDetails,deleteIndexedItem,saveStopwordsKeywords,wordDetail',
    ],
    [
        'access' => 'admin',
        'icon'   => 'EXT:indexed_search/Resources/Public/Icons/module-indexed_search.svg',
        'labels' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf',
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('index_config');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('index_config', 'EXT:indexed_search/Resources/Private/Language/locallang_csh_indexcfg.xlf');
