<?php
defined('TYPO3_MODE') or die();

// Register "old" FE plugin and hide layout, select_key and pages fields in BE
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:mod_indexed_search', 'indexed_search'],
    'list_type',
    'indexed_search'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexed_search'] = 'layout,select_key,pages';

// Registers "new" extbase based FE plugin and hide layout, select_key, pages and recursive fields in BE
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'TYPO3.CMS.IndexedSearch',
    'Pi2',
    'Indexed Search (Extbase & Fluid based)'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexedsearch_pi2'] = 'layout,select_key,pages,recursive';
