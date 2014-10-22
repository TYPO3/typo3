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
		'showitem' => 'doktype;;2, hidden, nav_hide, title;;3, subtitle,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
				starttime, endtime, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.options,
				TSconfig;;6;nowrap, storage_pid;;7, l18n_cfg, backend_layout;;8,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
	'
	);
	// Add doktype 2 ("Advanced")
	$GLOBALS['TCA']['pages']['types'][2] = array(
		'showitem' => 'doktype;;2, hidden, nav_hide, title;;3, subtitle, nav_title,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
				abstract;;5, keywords, description,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
				starttime, endtime, fe_login_mode, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.options,
				TSconfig;;6;nowrap, storage_pid;;7, l18n_cfg, module, content_from_pid, backend_layout;;8,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
	'
	);
}
