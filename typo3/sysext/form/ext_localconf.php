<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Form\Utility\FormUtility::getInstance()->initializeFormObjects()->initializePageTsConfig();

// Add a for previewing tt_content elements of CType="mailform"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['mailform'] = \TYPO3\CMS\Form\Hooks\PageLayoutView\MailformPreviewRenderer::class;


// Add the form CType to the "New Content Element" wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	mod.wizards.newContentElement.wizardItems.forms {
		elements.mailform {
			icon = EXT:frontend/Resources/Public/Icons/ContentElementWizard/mailform.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_mail_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_mail_description
			tt_content_defValues.CType = mailform
		}
		show :=addToList(mailform)
	}
');
