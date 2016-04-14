<?php
defined('TYPO3_MODE') or die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('indexed_search')) {
    // register pibase plugin
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'indexed_search',
        'setup',
        trim('
            plugin.tx_indexedsearch = USER_INT
            plugin.tx_indexedsearch.userFunc = ' . \TYPO3\CMS\Compatibility7\Controller\SearchFormController::class . '->main
        ')
    );
    // add default rendering for pibase plugin
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'indexed_search',
        'setup',
        'tt_content.list.20.indexed_search =< plugin.tx_indexedsearch',
        'defaultContentRendering'
    );
}
