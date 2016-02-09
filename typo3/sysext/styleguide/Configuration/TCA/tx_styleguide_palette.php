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
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
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
                'size' => '13',
                'max' => '20',
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
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
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
        'palette_6_2' => [
            'exclude' => 1,
            'label' => 'palette_6_2',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_6_3' => [
            'exclude' => 1,
            'label' => 'palette_6_3',
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
    ],


];
