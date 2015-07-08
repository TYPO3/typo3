<?php
defined('TYPO3_MODE') or die();

// add plugin controller
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('TYPO3.CMS.Felogin', 'setup', '
# Setting "felogin" plugin TypoScript
plugin.tx_felogin_pi1 = USER_INT
plugin.tx_felogin_pi1.userFunc = TYPO3\\CMS\\Felogin\\Controller\\FrontendLoginController->main
');

// Add a default TypoScript for the CType "login" (also replaces history login functionality)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('TYPO3.CMS.Felogin', 'setup', '
# Setting "felogin" plugin TypoScript
tt_content.login = COA
tt_content.login {
	10 =< lib.stdheader
	20 >
	20 =< plugin.tx_felogin_pi1
}
', 'defaultContentRendering');

// add login to new content element wizard
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	mod.wizards.newContentElement.wizardItems.forms {
		elements.login {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/login_form.gif
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_login_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_login_description
			tt_content_defValues {
				CType = login
			}
		}
		show :=addToList(login)
	}
	');
}

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['felogin'] = \TYPO3\CMS\Felogin\Hooks\CmsLayout::class;
