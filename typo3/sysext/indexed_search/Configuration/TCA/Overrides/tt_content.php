<?php
defined('TYPO3_MODE') or die();

// Registers "new" extbase based FE plugin and hide layout, select_key, pages and recursive fields in BE
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'TYPO3.CMS.IndexedSearch',
    'Pi2',
    'Indexed Search (Extbase & Fluid based)'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexedsearch_pi2'] = 'layout,select_key,pages,recursive';
