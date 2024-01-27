<?php

defined('TYPO3') or die();

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'IndexedSearch',
        'Pi2',
        'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:plugin_title',
        'mimetypes-x-content-form-search',
        'forms',
        'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:plugin_description',
    );
});
