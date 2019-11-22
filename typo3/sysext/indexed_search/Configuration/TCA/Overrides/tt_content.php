<?php
defined('TYPO3_MODE') or die();

// Registers FE plugin and hide layout, pages and recursive fields in BE
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'IndexedSearch',
    'Pi2',
    'Indexed Search'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexedsearch_pi2'] = 'layout,pages,recursive';
