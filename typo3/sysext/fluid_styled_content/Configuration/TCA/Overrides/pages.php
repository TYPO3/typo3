<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_content'], ['allowed_classes' => false]);
    if (isset($extConf['loadContentElementWizardTsConfig']) && (int)$extConf['loadContentElementWizardTsConfig'] === 0) {
        // Add pageTSconfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
            'fluid_styled_content',
            'Configuration/PageTSconfig/NewContentElementWizard.ts',
            'Fluid-based Content Elements'
        );
    }
});
