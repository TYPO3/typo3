<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// define the plugin execution
$pluginContent = '# Defining "felogin" plugin TypoScript
plugin.tx_felogin_pi1 = USER_INT
plugin.tx_felogin_pi1.userFunc = TYPO3\\CMS\\Felogin\\Controller\\FrontendLoginController->main
';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $pluginContent);

// replace old Login with felogin
$replaceLoginCType = '
# Setting "felogin" plugin to replace default "login" content element via TypoScript
tt_content.login = COA
tt_content.login {
	10 = < lib.stdheader
	20 >
	20 = < plugin.tx_felogin_pi1
}
';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $replaceLoginCType, 43);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
mod.wizards.newContentElement.wizardItems.forms {
	elements {
		login {
			icon = gfx/c_wiz/login_form.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_login_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_login_description
			tt_content_defValues {
				CType = login
			}
		}
	}
	show :=addToList(login)
}
');

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][$_EXTKEY] = 'TYPO3\\CMS\\Felogin\\Hooks\\CmsLayout';
