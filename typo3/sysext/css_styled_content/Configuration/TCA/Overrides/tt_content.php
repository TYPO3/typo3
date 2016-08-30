<?php
defined('TYPO3_MODE') or die();

//Extra fields for the tt_content table
$extraContentColumns = [
    'header_position' => [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position',
        'exclude' => true,
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
                    ''
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.1',
                    'center'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.2',
                    'right'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.3',
                    'left'
                ]
            ],
            'default' => ''
        ]
    ],
    'image_compression' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
                    0
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression.I.1',
                    1
                ],
                [
                    'GIF/256',
                    10
                ],
                [
                    'GIF/128',
                    11
                ],
                [
                    'GIF/64',
                    12
                ],
                [
                    'GIF/32',
                    13
                ],
                [
                    'GIF/16',
                    14
                ],
                [
                    'GIF/8',
                    15
                ],
                [
                    'PNG',
                    39
                ],
                [
                    'PNG/256',
                    30
                ],
                [
                    'PNG/128',
                    31
                ],
                [
                    'PNG/64',
                    32
                ],
                [
                    'PNG/32',
                    33
                ],
                [
                    'PNG/16',
                    34
                ],
                [
                    'PNG/8',
                    35
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression.I.15',
                    21
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression.I.16',
                    22
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression.I.17',
                    24
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression.I.18',
                    26
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression.I.19',
                    28
                ]
            ]
        ]
    ],
    'image_effects' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.0',
                    0
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.1',
                    1
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.2',
                    2
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.3',
                    3
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.4',
                    10
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.5',
                    11
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.6',
                    20
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.7',
                    23
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.8',
                    25
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects.I.9',
                    26
                ]
            ]
        ]
    ],
    'image_noRows' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_noRows',
        'config' => [
            'type' => 'check',
            'items' => [
                '1' => [
                    '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_noRows.I.0'
                ]
            ]
        ]
    ],
    'section_frame' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    '',
                    '0'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.1',
                    '1'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.2',
                    '5'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.3',
                    '6'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.4',
                    '10'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.5',
                    '11'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.6',
                    '12'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.7',
                    '20'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame.I.8',
                    '21'
                ]
            ],
            'default' => '0'
        ]
    ],
    'spaceAfter' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:spaceAfter',
        'config' => [
            'type' => 'input',
            'size' => '5',
            'max' => '5',
            'eval' => 'int',
            'range' => [
                'lower' => '0'
            ],
            'default' => 0
        ]
    ],
    'spaceBefore' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:spaceBefore',
        'config' => [
            'type' => 'input',
            'size' => '5',
            'max' => '5',
            'eval' => 'int',
            'range' => [
                'lower' => '0'
            ],
            'default' => 0
        ]
    ],
    'table_bgColor' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
                    '0'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.1',
                    '1'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.2',
                    '2'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.3',
                    '200'
                ],
                [
                    '-----',
                    '--div--'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.5',
                    '240'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.6',
                    '241'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.7',
                    '242'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.8',
                    '243'
                ],
                [
                    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor.I.9',
                    '244'
                ]
            ],
            'default' => '0'
        ]
    ],
    'table_border' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_border',
        'config' => [
            'type' => 'input',
            'size' => '3',
            'max' => '3',
            'eval' => 'int',
            'range' => [
                'upper' => '20',
                'lower' => '0'
            ],
            'default' => 0
        ]
    ],
    'table_cellpadding' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_cellpadding',
        'config' => [
            'type' => 'input',
            'size' => '3',
            'max' => '3',
            'eval' => 'int',
            'range' => [
                'upper' => '200',
                'lower' => '0'
            ],
            'default' => 0
        ]
    ],
    'table_cellspacing' => [
        'exclude' => true,
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_cellspacing',
        'config' => [
            'type' => 'input',
            'size' => '3',
            'max' => '3',
            'eval' => 'int',
            'range' => [
                'upper' => '200',
                'lower' => '0'
            ],
            'default' => 0
        ]
    ]
];

// Adding fields to the tt_content table definition in TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $extraContentColumns);

// Add flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:css_styled_content/flexform_ds.xml', 'table');

// Add content elements
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] = array_merge(
    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'],
    [
        'textpic' => 'mimetypes-x-content-text-picture',
        'image' => 'mimetypes-x-content-image',
        'text' => 'mimetypes-x-content-text'
    ]
);
array_splice(
    $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'],
    2,
    0,
    [
        [
            'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.1',
            'text',
            'content-text'
        ],
        [
            'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.2',
            'textpic',
            'content-textpic'
        ],
        [
            'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.3',
            'image',
            'content-image'
        ]
    ]
);
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['default'] = 'text';

// Add palettes
$GLOBALS['TCA']['tt_content']['palettes'] = array_replace(
    $GLOBALS['TCA']['tt_content']['palettes'],
    [
        'header' => [
            'showitem' => '
				header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
				--linebreak--,
				header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
				header_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position_formlabel,
				date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel,
				--linebreak--,
				header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel
			',
        ],
        'headers' => [
            'showitem' => '
				header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
				--linebreak--,
				header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
				header_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position_formlabel,
				date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel,
				--linebreak--,
				header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel,
				--linebreak--,
				subheader;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader_formlabel
			',
        ],
        'imageblock' => [
            'showitem' => '
				imageorient;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient_formlabel,
				imagecols;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols_formlabel,
				--linebreak--,
				image_noRows;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_noRows_formlabel,
				imagecaption_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecaption_position_formlabel
			',
        ],
        'tablelayout' => [
            'showitem' => '
				table_bgColor;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_bgColor_formlabel,
				table_border;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_border_formlabel,
				table_cellspacing;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_cellspacing_formlabel,
				table_cellpadding;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:table_cellpadding_formlabel
			',
        ],
        'visibility' => [
            'showitem' => '
				hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden_formlabel,
				sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel,
				linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel
			',
        ],
        'frames' => [
            'showitem' => '
				layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
				spaceBefore;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:spaceBefore_formlabel,
				spaceAfter;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:spaceAfter_formlabel,
				section_frame;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame_formlabel
			',
        ]
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'image_settings',
    'imagewidth;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth_formlabel,
		imageheight;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageheight_formlabel,
		imageborder;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder_formlabel,
		--linebreak--,
		image_compression;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression_formlabel,
		image_effects;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects_formlabel,'
);

// Field arrangement for CE "header"
$GLOBALS['TCA']['tt_content']['types']['header']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "text"
$GLOBALS['TCA']['tt_content']['types']['text']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
		bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';
if (!is_array($GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides'])) {
    $GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides'] = [];
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext'])) {
    $GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext'] = [];
}
$baseDefaultExtrasOfBodytext = '';
if (!empty($GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'])) {
    $baseDefaultExtrasOfBodytext = $GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'] . ':';
}
$GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'richtext:rte_transform[mode=ts_css]';

// Field arrangement for CE "textpic"
$GLOBALS['TCA']['tt_content']['types']['textpic']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
		bodytext;Text,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images,
		image,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks;imagelinks,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.image_settings;image_settings,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imageblock;imageblock,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';
if (!is_array($GLOBALS['TCA']['tt_content']['types']['textpic']['columnsOverrides'])) {
    $GLOBALS['TCA']['tt_content']['types']['textpic']['columnsOverrides'] = [];
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['textpic']['columnsOverrides']['bodytext'])) {
    $GLOBALS['TCA']['tt_content']['types']['textpic']['columnsOverrides']['bodytext'] = [];
}
$GLOBALS['TCA']['tt_content']['types']['textpic']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'richtext:rte_transform[mode=ts_css]';

// Field arrangement for CE "image"
$GLOBALS['TCA']['tt_content']['types']['image']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images,
		image,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks;imagelinks,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.image_settings;image_settings,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imageblock;imageblock,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "bullets"
$GLOBALS['TCA']['tt_content']['types']['bullets']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
		bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';
if (!is_array($GLOBALS['TCA']['tt_content']['types']['bullets']['columnsOverrides'])) {
    $GLOBALS['TCA']['tt_content']['types']['bullets']['columnsOverrides'] = [];
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['bullets']['columnsOverrides']['bodytext'])) {
    $GLOBALS['TCA']['tt_content']['types']['bullets']['columnsOverrides']['bodytext'] = [];
}
$GLOBALS['TCA']['tt_content']['types']['bullets']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'nowrap';

// Field arrangement for CE "table"
$GLOBALS['TCA']['tt_content']['types']['table']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.5,
		cols,
		bodytext,
		pi_flexform,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.table_layout;tablelayout,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';
if (!is_array($GLOBALS['TCA']['tt_content']['types']['table']['columnsOverrides'])) {
    $GLOBALS['TCA']['tt_content']['types']['table']['columnsOverrides'] = [];
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['table']['columnsOverrides']['bodytext'])) {
    $GLOBALS['TCA']['tt_content']['types']['table']['columnsOverrides']['bodytext'] = [];
}
$GLOBALS['TCA']['tt_content']['types']['table']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'nowrap:wizards[table]';

// Field arrangement for CE "uploads"
$GLOBALS['TCA']['tt_content']['types']['uploads']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media;uploads,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.uploads_layout;uploadslayout,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "menu"
$GLOBALS['TCA']['tt_content']['types']['menu']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu;menu,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu_accessibility;menu_accessibility,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "shortcut"
$GLOBALS['TCA']['tt_content']['types']['shortcut']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.shortcut_formlabel,rowDescription,
		records;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:records_formlabel,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "list"
$GLOBALS['TCA']['tt_content']['types']['list']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
		list_type;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:list_type_formlabel,
		select_key;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:select_key_formlabel,
		pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.list_formlabel,
		recursive,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "div"
$GLOBALS['TCA']['tt_content']['types']['div']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.div_formlabel,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

// Field arrangement for CE "html"
$GLOBALS['TCA']['tt_content']['types']['html']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.html_formlabel,rowDescription,
		bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.html_formlabel,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';

$GLOBALS['TCA']['tt_content']['columns']['section_frame']['config']['items'][0] = [
    'LLL:EXT:css_styled_content/Resources/Private/Language/locallang_db.xlf:tt_content.tx_cssstyledcontent_section_frame.I.0', '0'
];

$GLOBALS['TCA']['tt_content']['columns']['section_frame']['config']['items'][9] = [
    'LLL:EXT:css_styled_content/Resources/Private/Language/locallang_db.xlf:tt_content.tx_cssstyledcontent_section_frame.I.9', '66'
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/', 'CSS Styled Content');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v6.2/', 'CSS Styled Content TYPO3 v6.2');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable('css_styled_content', 'tt_content', 'categories', [], true);
