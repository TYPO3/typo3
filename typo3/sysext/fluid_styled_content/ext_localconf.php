<?php
defined('TYPO3_MODE') or die();

// Define TypoScript as content rendering template
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'fluidstyledcontent/Configuration/TypoScript/Static/';

// Register for hook to show preview of tt_content element of CType="textmedia" in page module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['textmedia'] = \TYPO3\CMS\FluidStyledContent\Hooks\TextmediaPreviewRenderer::class;

if (TYPO3_MODE === 'BE') {
    call_user_func(
        function ($extKey) {
            // Get the extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);

            if (!isset($extConf['loadContentElementWizardTsConfig']) || (int)$extConf['loadContentElementWizardTsConfig'] === 1) {
                // Include new content elements to modWizards
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:fluid_styled_content/Configuration/PageTSconfig/NewContentElementWizard.ts">');
            }

            $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
            $dispatcher->connect(
                \TYPO3\CMS\Extensionmanager\Controller\ConfigurationController::class,
                'afterExtensionConfigurationWrite',
                \TYPO3\CMS\FluidStyledContent\Hooks\TcaCacheClearing::class,
                'clearTcaCache'
            );
        },
        $_EXTKEY
    );
}
