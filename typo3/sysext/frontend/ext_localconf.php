<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'FE' && !isset($_REQUEST['eID'])) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
		\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class,
		'recordPostRetrieval',
		\TYPO3\CMS\Frontend\Aspect\FileMetadataOverlayAspect::class,
		'languageAndWorkspaceOverlay'
	);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
	'frontend',
	'setup',
	'config.extTarget = _top'
);


if (TYPO3_MODE === 'FE') {

	// Register eID provider for showpic
	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_cms_showpic'] = 'EXT:frontend/Resources/PHP/Eid/ShowPic.php';
	// Register eID provider for ExtDirect for the frontend
	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['ExtDirect'] = 'EXT:frontend/Resources/PHP/Eid/ExtDirect.php';

	// Register the core media wizard provider
	\TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderManager::registerMediaWizardProvider(\TYPO3\CMS\Frontend\MediaWizard\MediaWizardProvider::class);

	// Register all available content objects
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'], array(
		'TEXT'             => \TYPO3\CMS\Frontend\ContentObject\TextContentObject::class,
		'CASE'             => \TYPO3\CMS\Frontend\ContentObject\CaseContentObject::class,
		'COBJ_ARRAY'       => \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject::class,
		'COA'              => \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject::class,
		'COA_INT'          => \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject::class,
		'USER'             => \TYPO3\CMS\Frontend\ContentObject\UserContentObject::class,
		'USER_INT'         => \TYPO3\CMS\Frontend\ContentObject\UserInternalContentObject::class,
		'FILE'             => \TYPO3\CMS\Frontend\ContentObject\FileContentObject::class,
		'FILES'            => \TYPO3\CMS\Frontend\ContentObject\FilesContentObject::class,
		'IMAGE'            => \TYPO3\CMS\Frontend\ContentObject\ImageContentObject::class,
		'IMG_RESOURCE'     => \TYPO3\CMS\Frontend\ContentObject\ImageResourceContentObject::class,
		'CONTENT'          => \TYPO3\CMS\Frontend\ContentObject\ContentContentObject::class,
		'RECORDS'          => \TYPO3\CMS\Frontend\ContentObject\RecordsContentObject::class,
		'HMENU'            => \TYPO3\CMS\Frontend\ContentObject\HierarchicalMenuContentObject::class,
		'CASEFUNC'         => \TYPO3\CMS\Frontend\ContentObject\CaseContentObject::class,
		'LOAD_REGISTER'    => \TYPO3\CMS\Frontend\ContentObject\LoadRegisterContentObject::class,
		'RESTORE_REGISTER' => \TYPO3\CMS\Frontend\ContentObject\RestoreRegisterContentObject::class,
		'TEMPLATE'         => \TYPO3\CMS\Frontend\ContentObject\TemplateContentObject::class,
		'FLUIDTEMPLATE'    => \TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject::class,
		'MULTIMEDIA'       => \TYPO3\CMS\Frontend\ContentObject\MultimediaContentObject::class,
		'MEDIA'            => \TYPO3\CMS\Frontend\ContentObject\MediaContentObject::class,
		'SWFOBJECT'        => \TYPO3\CMS\Frontend\ContentObject\ShockwaveFlashObjectContentObject::class,
		'FLOWPLAYER'       => \TYPO3\CMS\Frontend\ContentObject\FlowPlayerContentObject::class,
		'QTOBJECT'         => \TYPO3\CMS\Frontend\ContentObject\QuicktimeObjectContentObject::class,
		'SVG'              => \TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject::class,
		'EDITPANEL'        => \TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject::class
	));
}
