<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - imageManipulation',
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
        ],
    ],

    'columns' => [

        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Disable'],
                ],
            ],
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_group',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_group}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_group}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
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
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_group',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_group}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_group}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'group_db_1' => [
            'exclude' => 1,
            'label' => 'group_db_1',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_file',
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'group_db_2' => [
            'exclude' => 1,
            'label' => 'group_db_2',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_file',
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'group_db_3' => [
            'exclude' => 1,
            'label' => 'group_db_3',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_file',
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'crop_1' => [
            'exclude' => 1,
            'label' => 'crop_1',
            'description' => 'standard configuration',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_1',
            ],
        ],
        'crop_2' => [
            'exclude' => 1,
            'label' => 'crop_2',
            'description' => 'limit to png',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_2',
                'allowedExtensions' => 'png',
            ],
        ],
        'crop_4' => [
            'exclude' => 1,
            'label' => 'crop_4',
            'description' => 'limit to jpg',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_2',
                'allowedExtensions' => 'jpg',
            ],
        ],
        'crop_3' => [
            'exclude' => 1,
            'label' => 'crop_3',
            'description' => 'one crop variant',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3',
                'cropVariants' => [
                    'default' => [
                        'title' => 'foo bar',
                        'allowedAspectRatios' => [
                            '16:9' => [
                                'title' => '16 / 9',
                                'value' => 16 / 9,
                            ],
                            '3:2' => [
                                'title' => '3 / 2',
                                'value' => 3 / 2,
                            ],
                            '4:3' => [
                                'title' => '4 / 3',
                                'value' => 4 / 3,
                            ],
                            '1:1' => [
                                'title' => '1 / 1',
                                'value' => 1.0,
                            ],
                            'NaN' => [
                                'title' => 'free',
                                'value' => 0.0,
                            ],
                        ],
                        'selectedRatio' => 'NaN',
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ],
                ],
            ],
        ],
        'crop_5' => [
            'exclude' => 1,
            'label' => 'crop_5',
            'description' => 'several cropVariants',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3', 'cropVariants' => [
                    'mobile' => [
                        'title' => 'mobile',
                        'allowedAspectRatios' => [
                            '1:1' => [
                                'title' => '1 / 1',
                                'value' => 1.0,
                            ],
                        ],
                    ],
                    'desktop' => [
                        'title' => 'desktop',
                        'allowedAspectRatios' => [
                            '4:3' => [
                                'title' => '4 / 3',
                                'value' => 4 / 3,
                            ],
                            'NaN' => [
                                'title' => 'free',
                                'value' => 0.0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'crop_6' => [
            'exclude' => 1,
            'label' => 'crop_6',
            'description' => 'initial crop area',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3',
                'cropVariants' => [
                    'default' => [
                        'title' => 'foo bar',
                        'allowedAspectRatios' => [
                            '1:1' => [
                                'title' => '1 / 1',
                                'value' => 1.0,
                            ],
                        ],
                        'selectedRatio' => '1:1',
                        'cropArea' => [
                            'x' => 0,
                            'y' => 0,
                            'width' => 0.8,
                            'height' => 0.8,
                        ],
                    ],
                ],
            ],
        ],
        'crop_7' => [
            'exclude' => 1,
            'label' => 'crop_7',
            'description' => 'with focus area',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3',
                'cropVariants' => [
                    'default' => [
                        'title' => 'foo bar',
                        'allowedAspectRatios' => [
                            '1:1' => [
                                'title' => '1 / 1',
                                'value' => 1.0,
                            ],
                        ],
                        'selectedRatio' => '1:1',
                        'focusArea' => [
                            'x' => 1 / 4,
                            'y' => 1 / 4,
                            'width' => 3 / 4,
                            'height' => 3 / 4,
                        ],
                    ],
                ],
            ],
        ],
        'crop_8' => [
            'exclude' => 1,
            'label' => 'crop_8',
            'description' => 'crop variant with cover areas',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3',
                'cropVariants' => [
                    'default' => [
                        'title' => 'foo bar',
                        'allowedAspectRatios' => [
                            '1:1' => [
                                'title' => '1 / 1',
                                'value' => 1.0,
                            ],
                        ],
                        'selectedRatio' => '1:1',
                        'coverAreas' => [
                            [
                                'x' => 0.05,
                                'y' => 0.85,
                                'width' => 0.9,
                                'height' => 0.1,
                            ],
                            [
                                'x' => 0.05,
                                'y' => 0.05,
                                'width' => 1 / 4,
                                'height' => 1 / 4,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;crop,
                    group_db_1, crop_1, group_db_2, crop_2, crop_4, group_db_3,
                    crop_3, crop_5, crop_6, crop_7, crop_8,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
