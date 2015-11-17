<?php
defined('TYPO3_MODE') or die();

call_user_func(
    function ($extKey) {
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);

        if (isset($extConf['loadContentElementWizardTsConfig']) && (int)$extConf['loadContentElementWizardTsConfig'] === 0) {
            // Add pageTSconfig
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
                $extKey,
                'Configuration/PageTSconfig/NewContentElementWizard.ts',
                'CSS-based Content Elements'
            );
        }
    },
    'css_styled_content'
);
