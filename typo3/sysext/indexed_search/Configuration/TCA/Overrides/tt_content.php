<?php

defined('TYPO3') or die();

call_user_func(static function () {
    // Registers FE plugin and hide layout, pages and recursive fields in BE
    $pluginSignature = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'IndexedSearch',
        'Pi2',
        'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:plugin_title',
        'mimetypes-x-content-form-search',
        'forms',
        'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:plugin_description',
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,pages,recursive';
});
