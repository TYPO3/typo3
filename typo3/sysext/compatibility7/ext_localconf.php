<?php

defined('TYPO3_MODE') or die();

// Indexed search
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

// Content element
if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:compatibility7/Configuration/PageTS/Mod/Wizards/NewContentElementMenu.txt">'
    );
}
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('css_styled_content')) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'compatibility7/Configuration/TypoScript/ContentElement/CssStyledContent/';
}
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fluid_styled_content')) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'compatibility7/Configuration/TypoScript/ContentElement/FluidStyledContent/';
    // Set alias for deprecated fluid styled content menu viewhelper
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\AbstractMenuViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\AbstractMenuViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\AbstractMenuViewHelper'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\CategoriesViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\CategoriesViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\CategoriesViewHelper'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\DirectoryViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\DirectoryViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\DirectoryViewHelper'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\KeywordsViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\KeywordsViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\KeywordsViewHelper'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\ListViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\ListViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\ListViewHelper'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\MenuViewHelperTrait')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\MenuViewHelperTrait::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\MenuViewHelperTrait'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\SectionViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\SectionViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\SectionViewHelper'
        );
    }
    if (!class_exists('TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\UpdatedViewHelper')) {
        class_alias(
            \TYPO3\CMS\Compatibility7\ViewHelpers\Menu\UpdatedViewHelper::class,
            'TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu\UpdatedViewHelper'
        );
    }
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
