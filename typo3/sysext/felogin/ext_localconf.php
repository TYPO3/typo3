<?php

defined('TYPO3_MODE') or die();

// Add default TypoScript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
    "@import 'EXT:felogin/Configuration/TypoScript/constants.typoscript'"
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    "@import 'EXT:felogin/Configuration/TypoScript/setup.typoscript'"
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Felogin',
    'Login',
    [
        \TYPO3\CMS\FrontendLogin\Controller\LoginController::class => 'login, overview',
        \TYPO3\CMS\FrontendLogin\Controller\PasswordRecoveryController::class => 'recovery,showChangePassword,changePassword'
    ],
    [
        \TYPO3\CMS\FrontendLogin\Controller\LoginController::class => 'login, overview',
        \TYPO3\CMS\FrontendLogin\Controller\PasswordRecoveryController::class => 'recovery,showChangePassword,changePassword'
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Add login form to new content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:felogin/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig'"
);

// Add migration wizards
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Felogin\Updates\MigrateFeloginPlugins::class]
    = \TYPO3\CMS\Felogin\Updates\MigrateFeloginPlugins::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\FrontendLogin\Updates\MigrateFeloginPluginsCtype::class]
    = \TYPO3\CMS\FrontendLogin\Updates\MigrateFeloginPluginsCtype::class;
