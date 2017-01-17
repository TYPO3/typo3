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

// Enable pages.url_scheme functionality again
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks']['compatibility7_urlscheme']
    = \TYPO3\CMS\Compatibility7\Hooks\EnforceUrlSchemeHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing']['compatibility7_urlscheme']
    = \TYPO3\CMS\Compatibility7\Hooks\EnforceUrlSchemeHook::class . '->redirectIfUrlSchemeDoesNotMatch';

// Enable action `QuickEdit` in page layout controller again
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Backend\Controller\PageLayoutController::class]['initActionHook']['compatibility7_quickedit']
    = \TYPO3\CMS\Compatibility7\Hooks\PageLayoutActionHook::class . '->initAction';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Backend\Controller\PageLayoutController::class]['renderActionHook']['compatibility7_quickedit']
    = \TYPO3\CMS\Compatibility7\Hooks\PageLayoutActionHook::class . '->renderAction';
