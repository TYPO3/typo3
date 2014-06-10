<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Register "old" FE plugin and hide layout, select_key and pages fields in BE
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:indexed_search/locallang.xlf:mod_indexed_search', 'indexed_search'),
	'list_type',
	'indexed_search'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexed_search'] = 'layout,select_key,pages';

// Registers "new" extbase based FE plugin and hide layout, select_key, pages and recursive fields in BE
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'indexed_search',
	'Pi2',
	'Indexed Search (experimental)'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['indexedsearch_pi2'] = 'layout,select_key,pages,recursive';
