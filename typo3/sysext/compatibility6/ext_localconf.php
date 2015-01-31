<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'FE') {

	// Register legacy content objects
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['IMGTEXT']  = \TYPO3\CMS\Compatibility6\ContentObject\ImageTextContentObject::class;
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['CLEARGIF'] = \TYPO3\CMS\Compatibility6\ContentObject\ClearGifContentObject::class;
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['CTABLE']   = \TYPO3\CMS\Compatibility6\ContentObject\ContentTableContentObject::class;
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['OTABLE']   = \TYPO3\CMS\Compatibility6\ContentObject\OffsetTableContentObject::class;
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['COLUMNS']  = \TYPO3\CMS\Compatibility6\ContentObject\ColumnsContentObject::class;
	$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['HRULER']   = \TYPO3\CMS\Compatibility6\ContentObject\HorizontalRulerContentObject::class;
}
