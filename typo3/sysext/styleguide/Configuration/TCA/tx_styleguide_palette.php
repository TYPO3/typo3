<?php
return [
    'ctrl' => [
        'title' => 'Form engine - palette',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],


    'columns' => [


        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => '0'
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                ]
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_palette',
                'foreign_table_where' => 'AND tx_styleguide_palette.pid=###CURRENT_PID### AND tx_styleguide_palette.sys_language_uid IN (-1,0)',
            ]
        ],
        'l10n_source' => [
            'exclude' => true,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_palette',
                'foreign_table_where' => 'AND tx_styleguide_palette.pid=###CURRENT_PID### AND tx_styleguide_palette.uid!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],

        'palette_1_1' => [
            'exclude' => 1,
            'label' => 'palette_1_1',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'palette_1_2' => [
            'exclude' => 1,
            'label' => 'palette_1_2',
            'config' => [
                'default' => true,
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUserPalette->render',
            ],
        ],
        'palette_1_3' => [
            'exclude' => 1,
            'label' => 'palette_1_3',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'palette_2_1' => [
            'exclude' => 1,
            'label' => 'palette_2_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_3_1' => [
            'exclude' => 1,
            'label' => 'palette_3_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_3_2' => [
            'exclude' => 1,
            'label' => 'palette_3_2',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_1' => [
            'exclude' => 1,
            'label' => 'palette_4_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_2' => [
            'exclude' => 1,
            'label' => 'palette_4_2',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_3' => [
            'exclude' => 1,
            'label' => 'palette_4_3',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_4' => [
            'exclude' => 1,
            'label' => 'palette_4_4',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_5_1' => [
            'exclude' => 1,
            'label' => 'palette_5_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_5_2' => [
            'exclude' => 1,
            'label' => 'palette_5_2',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_6_1' => [
            'exclude' => 1,
            'label' => 'palette_6_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_7_1' => [
            'exclude' => 1,
            'label' => 'palette_7_1',
            'config' => [
                'type' => 'input',
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;palette,
                    --palette--;palette_1;palette_1,
                    --palette--;palette_2;palette_2,
                    --palette--;palette_3;palette_3,
                    --palette--;;palette_4,
                    --palette--;palette_5;palette_5,
                --div--;hidden palette,
                    --palette--;palette_6;palette_6,
                    --palette--;palette_7 (palette_6 hidden);palette_7,
            ',
        ],
    ],


    'palettes' => [
        'palette_1' => [
            'showitem' => 'palette_1_1, palette_1_2, palette_1_3',
        ],
        'palette_2' => [
            'showitem' => 'palette_2_1',
        ],
        'palette_3' => [
            'showitem' => 'palette_3_1, palette_3_2',
        ],
        'palette_4' => [
            'showitem' => 'palette_4_1, palette_4_2, palette_4_3, --linebreak--, palette_4_4',
        ],
        'palette_5' => [
            'showitem' => 'palette_5_1, --linebreak--, palette_5_2',
        ],
        'palette_6' => [
            'showitem' => 'palette_6_1',
            'isHiddenPalette' => true,
        ],
        'palette_7' => [
            'showitem' => 'palette_7_1',
        ],
    ],


];
