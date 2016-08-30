<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.IndexedSearch',
        'web',
        'isearch',
        '',
        [
            'Administration' => 'index,pages,externalDocuments,statistic,statisticDetails,deleteIndexedItem,saveStopwordsKeywords,wordDetail',
        ],
        [
            'access' => 'admin',
            'icon'   => 'EXT:indexed_search/Resources/Public/Icons/module-indexed_search.svg',
            'labels' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('index_config');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('index_config', 'EXT:indexed_search/Resources/Private/Language/locallang_csh_indexcfg.xlf');
