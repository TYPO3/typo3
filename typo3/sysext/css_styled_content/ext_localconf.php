<?php
defined('TYPO3_MODE') or die();

// Get the extension configuration
$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

// Disable image positions that make no sense on CType=image (it leaves just "above left", "center" and "right")
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	TCEFORM.tt_content.imageorient.types.image.removeItems = 8,9,10,17,18,25,26
');

// Mark the delivered TypoScript templates as "content rendering template" (providing the hooks of "static template 43" = content (default))
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'cssstyledcontent/static/';
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'cssstyledcontent/static/v6.2/';

// Register for hook to show preview of tt_content element of CType="image" in page module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['image'] =
    \TYPO3\CMS\CssStyledContent\Hooks\PageLayoutView\ImagePreviewRenderer::class;

// Register for hook to show preview of tt_content element of CType="textpic" in page module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['textpic'] =
    \TYPO3\CMS\CssStyledContent\Hooks\PageLayoutView\TextpicPreviewRenderer::class;

// Register for hook to show preview of tt_content element of CType="text" in page module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['text'] =
    \TYPO3\CMS\CssStyledContent\Hooks\PageLayoutView\TextPreviewRenderer::class;

if (TYPO3_MODE === 'BE') {
    call_user_func(
        function ($extKey) {
            // Get the extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);

            if (!isset($extConf['loadContentElementWizardTsConfig']) || (int)$extConf['loadContentElementWizardTsConfig'] === 1) {
                // Include new content elements to modWizards
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:css_styled_content/Configuration/PageTSconfig/NewContentElementWizard.ts">');
            }

            $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
            $dispatcher->connect(
                \TYPO3\CMS\Extensionmanager\Controller\ConfigurationController::class,
                'afterExtensionConfigurationWrite',
                \TYPO3\CMS\CssStyledContent\Hooks\TcaCacheClearing::class,
                'clearTcaCache'
            );
        },
        $_EXTKEY
    );
}
