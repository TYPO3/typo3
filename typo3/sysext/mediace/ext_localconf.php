<?php
defined('TYPO3_MODE') or die();

// Register additional content objects
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['MULTIMEDIA'] = \TYPO3\CMS\Mediace\ContentObject\MultimediaContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['MEDIA']      = \TYPO3\CMS\Mediace\ContentObject\MediaContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['SWFOBJECT']  = \TYPO3\CMS\Mediace\ContentObject\ShockwaveFlashObjectContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['FLOWPLAYER'] = \TYPO3\CMS\Mediace\ContentObject\FlowPlayerContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['QTOBJECT']   = \TYPO3\CMS\Mediace\ContentObject\QuicktimeObjectContentObject::class;

// Register the "media" CType to the "New Content Element" wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	mod.wizards.newContentElement.wizardItems {
		special.elements.media {
			iconIdentifier = content-special-media
			title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_media_title
			description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:special_media_description
			tt_content_defValues.CType = media
		}
		special.show := addToList(media)
	}
');

// Add Default TypoScript for CType "media" and "multimedia" after default content rendering
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('mediace', 'constants', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mediace/Configuration/TypoScript/constants.txt">', 'defaultContentRendering');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('mediace', 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mediace/Configuration/TypoScript/setup.txt">', 'defaultContentRendering');

if (TYPO3_MODE === 'FE') {
	// Register the basic media wizard provider
	\TYPO3\CMS\Mediace\MediaWizard\MediaWizardProviderManager::registerMediaWizardProvider(\TYPO3\CMS\Mediace\MediaWizard\MediaWizardProvider::class);
}

if (TYPO3_MODE === 'BE') {
	// Register for hook to show preview of tt_content element of CType="multimedia" in page module
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['multimedia'] =
		\TYPO3\CMS\Mediace\Hooks\PageLayoutView\MultimediaPreviewRenderer::class;
}
