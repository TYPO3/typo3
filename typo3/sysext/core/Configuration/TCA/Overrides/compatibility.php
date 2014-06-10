<?php
defined('TYPO3_MODE') or die();

// If the compat version is less than 4.2, pagetype 2 ("Advanced")
// and pagetype 5 ("Not in menu") are added to TCA.
if (!\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('4.2')) {
	// Merging in CMS doktypes
	array_splice($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'], 2, 0, array(
		array(
			'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.0',
			'2',
			'i/pages.gif'
		),
		array(
			'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.3',
			'5',
			'i/pages_notinmenu.gif'
		)
	));
	// Set the doktype 1 ("Standard") to show less fields
	$GLOBALS['TCA']['pages']['types'][1] = array(
		// standard
		'showitem' => 'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
				starttime, endtime, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.options,
				TSconfig;;6;nowrap;4-4-4, storage_pid;;7, l18n_cfg, backend_layout;;8,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
	'
	);
	// Add doktype 2 ("Advanced")
	$GLOBALS['TCA']['pages']['types'][2] = array(
		'showitem' => 'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle, nav_title,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
				abstract;;5;;3-3-3, keywords, description,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
				starttime, endtime, fe_login_mode, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.options,
				TSconfig;;6;nowrap;6-6-6, storage_pid;;7, l18n_cfg, module, content_from_pid, backend_layout;;8,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
	'
	);
}

// Keep old code (pre-FAL) for installations that haven't upgraded yet. please remove this code in TYPO3 6.2
// @deprecated since TYPO3 6.0, please remove at earliest in TYPO3 6.2
if (
	(
		!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard'])
		|| !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard'], 'pages:media')
	)
	&& !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')
) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
		'This installation hasn\'t been migrated to FAL for the field $GLOBALS[TCA][pages][columns][media] yet. Please do so before TYPO3 v7.'
	);
	// existing installation and no upgrade wizard was executed - and files haven't been merged: use the old code
	$GLOBALS['TCA']['pages']['columns']['media']['config'] = array(
		'type' => 'group',
		'internal_type' => 'file',
		'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . ',html,htm,ttf,txt,css',
		'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
		'uploadfolder' => 'uploads/media',
		'show_thumbs' => '1',
		'size' => '3',
		'maxitems' => '100',
		'minitems' => '0'
	);
}
