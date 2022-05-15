<?php

defined('TYPO3') or die();

// Registers FE plugin and hide layout, pages and recursive fields in BE
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'IndexedSearch',
    'Pi2',
    'Indexed Search',
    'mimetypes-x-content-form-search'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexedsearch_pi2'] = 'layout,pages,recursive';
