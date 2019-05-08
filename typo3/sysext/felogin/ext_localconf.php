<?php
defined('TYPO3_MODE') or die();

//Add default TypoScript
(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
        "@import 'EXT:felogin/Configuration/TypoScript/constants.typoscript'"
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        "@import 'EXT:felogin/Configuration/TypoScript/setup.typoscript'"
    );
})();

//Add additional TypoScript & TsConfig depending on the value of the feature toggle "felogin.extbase"
(static function (): void {
    $feloginExtbase = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\Features::class)
        ->isFeatureEnabled('felogin.extbase');

    if (!$feloginExtbase) {
        // Add a default TypoScript for the CType "login"
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
            "@import 'EXT:felogin/Configuration/TypoScript/PiBase/constants.typoscript'"
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
            "@import 'EXT:felogin/Configuration/TypoScript/PiBase/setup.typoscript'"
        );

        // Add login to new content element wizard
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            "@import 'EXT:felogin/Configuration/TsConfig/Page/PiBase/Mod/Wizards/NewContentElement.tsconfig'"
        );

        // Page module hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['felogin'] = \TYPO3\CMS\Felogin\Hooks\CmsLayout::class;
    } else {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'TYPO3.CMS.Felogin',
            'Login',
            [
                'Login' => 'login, overview'
            ],
            [
                'Login' => 'login, overview'
            ],
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );

        // Add login form to new content element wizard
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            "@import 'EXT:felogin/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig'"
        );
    }

    // Add migration wizard
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Felogin\Updates\MigrateFeloginPlugins::class]
        = \TYPO3\CMS\Felogin\Updates\MigrateFeloginPlugins::class;
})();
