<?php

return [
    'ctrl' => [
        'title' => 'Form engine - palette',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'palette_1_1' => [
            'label' => 'palette_1_1',
            'description' => 'field description',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'palette_1_3' => [
            'label' => 'palette_1_3',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'palette_2_1' => [
            'label' => 'palette_2_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_3_1' => [
            'label' => 'palette_3_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_3_2' => [
            'label' => 'palette_3_2',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_1' => [
            'label' => 'palette_4_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_2' => [
            'label' => 'palette_4_2 This is a really long label text. AndOneWordIsReallyEvenMuchLongerWhoWritesThoseLongWordsAnyways?',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_3' => [
            'label' => 'palette_4_3',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_4_4' => [
            'label' => 'palette_4_4',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_5_1' => [
            'label' => 'palette_5_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_5_2' => [
            'label' => 'palette_5_2',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_6_1' => [
            'label' => 'palette_6_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'palette_7_1' => [
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
                    --palette--;;palette_1,
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
            'label' => 'palette_1',
            'description' => 'palette description',
            'showitem' => 'palette_1_1, palette_1_3',
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
