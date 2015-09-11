<?php
defined('TYPO3_MODE') or die();

//Extra fields for the tt_content table
$extraContentColumns = array(
	'altText' => array(
		'exclude' => TRUE,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_altText',
		'config' => array(
			'type' => 'text',
			'cols' => '30',
			'rows' => '3'
		)
	),
	'imagecaption' => array(
		'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.caption',
		'config' => array(
			'type' => 'text',
			'cols' => '30',
			'rows' => '3',
			'softref' => 'typolink_tag,images,email[subst],url'
		)
	),
	'imagecaption_position' => array(
		'exclude' => TRUE,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecaption_position',
		'config' => array(
			'type' => 'select',
			'items' => array(
				array(
					'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
					''
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecaption_position.I.1',
					'center'
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecaption_position.I.2',
					'right'
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecaption_position.I.3',
					'left'
				)
			),
			'default' => ''
		)
	),
	'image_link' => array(
		'exclude' => TRUE,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_link',
		'config' => array(
			'type' => 'text',
			'cols' => '30',
			'rows' => '3',
			'wizards' => array(
				'link' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_link_formlabel',
					'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
					'module' => array(
						'name' => 'wizard_element_browser',
						'urlParameters' => array(
							'mode' => 'wizard'
						)
					),
					'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
				)
			),
			'softref' => 'typolink[linkList]'
		)
	),
	'image_frames' => array(
		'exclude' => TRUE,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames',
		'config' => array(
			'type' => 'select',
			'items' => array(
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.0',
					0
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.1',
					1
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.2',
					2
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.3',
					3
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.4',
					4
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.5',
					5
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.6',
					6
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.7',
					7
				),
				array(
					'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames.I.8',
					8
				)
			)
		)
	),
	'longdescURL' => array(
		'exclude' => TRUE,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_longdescURL',
		'config' => array(
			'type' => 'text',
			'cols' => '30',
			'rows' => '3',
			'wizards' => array(
				'link' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_link_formlabel',
					'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
					'module' => array(
						'name' => 'wizard_element_browser',
						'urlParameters' => array(
							'mode' => 'wizard'
						)
					),
					'params' => array(
						'blindLinkOptions' => 'folder,file,mail,spec',
						'blindLinkFields' => 'target,title,class,params'
					),
					'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
				)
			),
			'softref' => 'typolink[linkList]'
		)
	),
	'titleText' => array(
		'exclude' => TRUE,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_titleText',
		'config' => array(
			'type' => 'text',
			'cols' => '30',
			'rows' => '3'
		)
	)
);

// Adding fields to the tt_content table definition in TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $extraContentColumns);

// Add default palettes
$GLOBALS['TCA']['tt_content']['palettes'] = array_replace(
	$GLOBALS['TCA']['tt_content']['palettes'],
	array(
		'1' => array(
			'showitem' => '
				starttime,
				endtime
			'
		),
		'2' => array(
			'showitem' => '
				imagecols,
				image_noRows,
				imageborder
			'
		),
		'3' => array(
			'showitem' => '
				header_position,
				header_layout,
				header_link,
				date
			'
		),
		'4' => array(
			'showitem' => '
				sys_language_uid,
				l18n_parent,
				colPos,
				spaceBefore,
				spaceAfter,
				section_frame,
				sectionIndex
			'
		),
		'5' => array(
			'showitem' => '
				imagecaption_position
			'
		),
		'6' => array(
			'showitem' => '
				imagewidth,
				image_link
			'
		),
		'7' => array(
			'showitem' => '
				image_link,
				image_zoom
			',
		),
		'8' => array(
			'showitem' => '
				layout
			'
		),
		'10' => array(
			'showitem' => '
				table_bgColor,
				table_border,
				table_cellspacing,
				table_cellpadding
			'
		),
		'11' => array(
			'showitem' => '
				image_compression,
				image_effects,
				image_frames
			',
		),
		'12' => array(
			'showitem' => '
				recursive
			'
		),
		'13' => array(
			'showitem' => '
				imagewidth,
				imageheight
			',
		),
		'14' => array(
			'showitem' => '
				sys_language_uid,
				l18n_parent,
				colPos
			'
		),
		'image_accessibility' => array(
			'showitem' => '
				altText;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:altText_formlabel,
				titleText;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:titleText_formlabel,
				--linebreak--,
				longdescURL;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:longdescURL_formlabel
			',
		)
	)
);

// Add palettes from css_styled_content if css_styled_content is NOT loaded but needed for CE's "search" and "mailform"
if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('css_styled_content')) {
	$GLOBALS['TCA']['tt_content']['palettes'] = array_replace(
		$GLOBALS['TCA']['tt_content']['palettes'],
		array(
			'visibility' => array(
				'showitem' => '
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden_formlabel,
					sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel,
					linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel
				',
			),
			'frames' => array(
				'showitem' => '
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					spaceBefore;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:spaceBefore_formlabel,
					spaceAfter;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:spaceAfter_formlabel,
					section_frame;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame_formlabel
				',
			)
		)
	);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'image_settings', 'image_frames;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_frames_formlabel');

/**
 * CType "search"
 */
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['search'] = 'mimetypes-x-content-form-search';
$GLOBALS['TCA']['tt_content']['types']['search'] = array(
	'showitem' => '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
			--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
			--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
			--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
			--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.behaviour,
			--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.searchform;searchform,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended'
);

$GLOBALS['TCA']['tt_content']['palettes']['searchform'] = array(
	'showitem' => 'pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.searchform',
);

// check if there is already a forms tab and add the item after that, otherwise
// add the tab item as well
$additionalCTypeItem = array(
	'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.9',
	'search',
	'i/tt_content_search.gif'
);

$existingCTypeItems = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
$groupFound = FALSE;
$groupPosition = FALSE;
foreach ($existingCTypeItems as $position => $item) {
	if ($item[0] === 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms') {
		$groupFound = TRUE;
		$groupPosition = $position;
		break;
	}
}

if ($groupFound && $groupPosition) {
	// add the new CType item below CType
	array_splice($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'], $groupPosition+1, 0, array(0 => $additionalCTypeItem));
} else {
	// nothing found, add two items (group + new CType) at the bottom of the list
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType',
		array('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms', '--div--')
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', $additionalCTypeItem);
}


/**
 * CType "mailform"
 */
if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('form')) {
	$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['mailform'] = 'mimetypes-x-content-form';
	$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards']['forms'] = array(
		'notNewRecords' => 1,
		'enableByTypeConfig' => 1,
		'type' => 'script',
		'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.forms',
		'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif',
		'module' => array(
			'name' => 'wizard_forms',
			'urlParameters' => array(
				'special' => 'formtype_mail'
			)
		),
		'params' => array(
			'xmlOutput' => 0
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', array(
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.8',
			'mailform',
			'i/tt_content_form.gif'
		),
		'search',
		'before'
	);
}


// set up the fields
$GLOBALS['TCA']['tt_content']['types']['mailform'] = array(
	'showitem' => '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
		bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.mailform_formlabel,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.behaviour,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mailform;mailform,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended'
);
$baseDefaultExtrasOfBodytext = '';
if (!empty($GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'])) {
	$baseDefaultExtrasOfBodytext = $GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'] . ':';
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides'])) {
	$GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides'] = array();
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides']['bodytext'])) {
	$GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides']['bodytext'] = array();
}
$GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'nowrap:wizards[forms]';

$GLOBALS['TCA']['tt_content']['palettes']['mailform'] = array(
	'showitem' => 'pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.mailform, --linebreak--, subheader;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader.ALT.mailform_formlabel',
);
