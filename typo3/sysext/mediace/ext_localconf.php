<?php
defined('TYPO3_MODE') or die();

// Register additional content objects
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['MULTIMEDIA'] = \FoT3\Mediace\ContentObject\MultimediaContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['MEDIA']      = \FoT3\Mediace\ContentObject\MediaContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['SWFOBJECT']  = \FoT3\Mediace\ContentObject\ShockwaveFlashObjectContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['FLOWPLAYER'] = \FoT3\Mediace\ContentObject\FlowPlayerContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['QTOBJECT']   = \FoT3\Mediace\ContentObject\QuicktimeObjectContentObject::class;

// Register the "media" CType to the "New Content Element" wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	mod.wizards.newContentElement.wizardItems {
		special.elements.media {
			iconIdentifier = content-special-media
			title = LLL:EXT:mediace/Resources/Private/Language/locallang.xlf:newContentElementWizard.media.title
			description = LLL:EXT:mediace/Resources/Private/Language/locallang.xlf:newContentElementWizard.media.description
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
    \FoT3\Mediace\MediaWizard\MediaWizardProviderManager::registerMediaWizardProvider(\FoT3\Mediace\MediaWizard\MediaWizardProvider::class);
}

if (TYPO3_MODE === 'BE') {
    // Register for hook to show preview of tt_content element of CType="multimedia" in page module
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['multimedia'] =
        \FoT3\Mediace\Hooks\PageLayoutView\MultimediaPreviewRenderer::class;
}
