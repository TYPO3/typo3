<?php
defined('TYPO3_MODE') or die();

// Add a default TypoScript for the CType "login"
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
    "@import 'EXT:felogin/Configuration/TypoScript/constants.typoscript'"
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    "@import 'EXT:felogin/Configuration/TypoScript/setup.typoscript'"
);

// Add login to new content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:felogin/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig'"
);

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['felogin'] = \TYPO3\CMS\Felogin\Hooks\CmsLayout::class;

// Add migration wizard
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Felogin\Updates\MigrateFeloginPlugins::class]
    = \TYPO3\CMS\Felogin\Updates\MigrateFeloginPlugins::class;
