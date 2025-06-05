<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - imageManipulation',
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
        'group_db_1' => [
            'label' => 'group_db_1 (for crop_1)',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_file',
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'group_db_2' => [
            'label' => 'group_db_2 (for crop_2 and crop_4)',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_file',
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'group_db_3' => [
            'label' => 'group_db_3 (for crop_5 to crop_10)',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_file',
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'size' => 1,
            ],
        ],
        'crop_1' => [
            'label' => 'crop_1',
            'description' => 'standard configuration',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_1',
            ],
        ],
        'crop_2' => [
            'label' => 'crop_2',
            'description' => 'limit to png',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_2',
                'allowedExtensions' => 'png',
            ],
        ],
        'crop_4' => [
            'label' => 'crop_4',
            'description' => 'limit to jpg',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_2',
                'allowedExtensions' => 'jpg',
            ],
        ],
        'crop_3' => [
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
                            '1:7.50 [special {characters}]' => [
                                'title' => '1:7.50 [special {characters}]',
                                'value' => 1 / 7.5,
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
        'crop_9' => [
            'label' => 'crop_9',
            'description' => 'crop variant with multiple cover areas',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3',
                'cropVariants' => [
                    'desktop_wide' => [
                        'title' => 'Desktop wide',
                        'allowedAspectRatios' => [
                            'default' => [
                                'title' => 'Default',
                                'value' => 1920 / 680,
                            ],
                        ],
                        'selectedRatio' => 'default',
                        'coverAreas' => [
                            [
                                'x' => 0,
                                'y' => 0,
                                'width' => 1,
                                'height' => 0.25,
                            ],
                            [
                                'x' => 0.2,
                                'y' => 0.35,
                                'width' => 0.25,
                                'height' => 0.5,
                            ],
                        ],
                    ],
                    'desktop' => [
                        'title' => 'Desktop',
                        'allowedAspectRatios' => [
                            'default' => [
                                'title' => 'Default',
                                'value' => 1370 / 680,
                            ],
                        ],
                        'selectedRatio' => 'default',
                        'coverAreas' => [
                            [
                                'x' => 0,
                                'y' => 0,
                                'width' => 1,
                                'height' => 0.25,
                            ],
                            [
                                'x' => 0.08,
                                'y' => 0.35,
                                'width' => 0.45,
                                'height' => 0.5,
                            ],
                        ],
                    ],
                    'small' => [
                        'title' => 'Tablet / Smartphone',
                        'allowedAspectRatios' => [
                            'default' => [
                                'title' => 'Default',
                                'value' => 16 / 9,
                            ],
                        ],
                        'selectedRatio' => 'default',
                    ],
                ],
            ],
        ],
        'crop_10' => [
            'label' => 'crop_10',
            'description' => 'with multiple aspect, cover and focus areas',
            'config' => [
                'type' => 'imageManipulation',
                'file_field' => 'group_db_3',

                'cropVariants' => [
                    'desktop_wide' => [
                        'title' => 'Desktop wide',
                        'allowedAspectRatios' => [
                            'default' => [
                                'title' => 'Default',
                                'value' => 1920 / 680,
                            ],
                            'wide-landscape' => [
                                'title' => 'Landscape (Wide)',
                                'value' => 1920 / 400,
                            ],
                            'tall-portrait' => [
                                'title' => 'Portrait (Tall)',
                                'value' => 800 / 1920,
                            ],
                        ],
                        'selectedRatio' => 'default',
                        'coverAreas' => [
                            [
                                'x' => 0,
                                'y' => 0,
                                'width' => 1,
                                'height' => 0.25,
                            ],
                            [
                                'x' => 0.2,
                                'y' => 0.35,
                                'width' => 0.25,
                                'height' => 0.5,
                            ],
                        ],
                        // intentional overlap with cover area
                        'focusArea' => [
                            'x' => 1 / 4,
                            'y' => 1 / 4,
                            'width' => 1 / 4,
                            'height' => 1 / 6,
                        ],
                    ],
                    'desktop' => [
                        'title' => 'Desktop',
                        'allowedAspectRatios' => [
                            'default' => [
                                'title' => 'Default',
                                'value' => 1370 / 680,
                            ],
                            'wide-landscape' => [
                                'title' => 'Landscape (Wide)',
                                'value' => 1370 / 300,
                            ],
                            'tall-portrait' => [
                                'title' => 'Portrait (Tall)',
                                'value' => 600 / 1370,
                            ],
                        ],
                        'selectedRatio' => 'wide-landscape',
                        'coverAreas' => [
                            [
                                'x' => 0,
                                'y' => 0,
                                'width' => 1,
                                'height' => 0.25,
                            ],
                            [
                                'x' => 0.08,
                                'y' => 0.35,
                                'width' => 0.45,
                                'height' => 0.5,
                            ],
                        ],
                        'focusArea' => [
                            'x' => 3 / 4,
                            'y' => 1 / 4,
                            'width' => 1 / 6,
                            'height' => 2 / 6,
                        ],
                    ],
                    'small' => [
                        'title' => 'Tablet / Smartphone',
                        'allowedAspectRatios' => [
                            'sixteen_by_nine' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            'four_by_three' => [
                                'title' => '4:3',
                                'value' => 4 / 3,
                            ],
                            'ultrawide' => [
                                'title' => '21:9',
                                'value' => 21 / 9,
                            ],
                            'NaN' => [
                                'title' => 'free',
                                'value' => 0.0,
                            ],
                        ],
                        'selectedRatio' => 'sixteen_by_nine',
                        // no cover area by intention
                        'focusArea' => [
                            'x' => 1 / 4,
                            'y' => 1 / 4,
                            'width' => 3 / 4,
                            'height' => 3 / 4,
                        ],
                    ],
                    'watch' => [
                        'title' => 'Smartwatch',
                        'allowedAspectRatios' => [
                            'sixteen_by_nine' => [
                                'title' => '16:9',
                                'value' => 16 / 9,
                            ],
                            'four_by_three' => [
                                'title' => '4:3',
                                'value' => 4 / 3,
                            ],
                            'ultrawide' => [
                                'title' => '21:9',
                                'value' => 21 / 9,
                            ],
                            'NaN' => [
                                'title' => 'free',
                                'value' => 0.0,
                            ],
                        ],
                        'selectedRatio' => 'sixteen_by_nine',
                        // no focus area by intention
                        'coverAreas' => [
                            [
                                'x' => 0,
                                'y' => 0,
                                'width' => 1,
                                'height' => 0.25,
                            ],
                            [
                                'x' => 0.08,
                                'y' => 0.35,
                                'width' => 0.45,
                                'height' => 0.5,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'file_1' => [
            'label' => 'file_1 (crop in IRRE, TCA override)',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'description' => 'Custom crop (same as crop_10)',
                            'config' => [
                                'cropVariants' => [
                                    'desktop_wide' => [
                                        'title' => 'Desktop wide',
                                        'allowedAspectRatios' => [
                                            'default' => [
                                                'title' => 'Default',
                                                'value' => 1920 / 680,
                                            ],
                                            'wide-landscape' => [
                                                'title' => 'Landscape (Wide)',
                                                'value' => 1920 / 400,
                                            ],
                                            'tall-portrait' => [
                                                'title' => 'Portrait (Tall)',
                                                'value' => 800 / 1920,
                                            ],
                                        ],
                                        'selectedRatio' => 'default',
                                        'coverAreas' => [
                                            [
                                                'x' => 0,
                                                'y' => 0,
                                                'width' => 1,
                                                'height' => 0.25,
                                            ],
                                            [
                                                'x' => 0.2,
                                                'y' => 0.35,
                                                'width' => 0.25,
                                                'height' => 0.5,
                                            ],
                                        ],
                                        // intentional overlap with cover area
                                        'focusArea' => [
                                            'x' => 1 / 4,
                                            'y' => 1 / 4,
                                            'width' => 1 / 4,
                                            'height' => 1 / 6,
                                        ],
                                    ],
                                    'desktop' => [
                                        'title' => 'Desktop',
                                        'allowedAspectRatios' => [
                                            'default' => [
                                                'title' => 'Default',
                                                'value' => 1370 / 680,
                                            ],
                                            'wide-landscape' => [
                                                'title' => 'Landscape (Wide)',
                                                'value' => 1370 / 300,
                                            ],
                                            'tall-portrait' => [
                                                'title' => 'Portrait (Tall)',
                                                'value' => 600 / 1370,
                                            ],
                                        ],
                                        'selectedRatio' => 'wide-landscape',
                                        'coverAreas' => [
                                            [
                                                'x' => 0,
                                                'y' => 0,
                                                'width' => 1,
                                                'height' => 0.25,
                                            ],
                                            [
                                                'x' => 0.08,
                                                'y' => 0.35,
                                                'width' => 0.45,
                                                'height' => 0.5,
                                            ],
                                        ],
                                        'focusArea' => [
                                            'x' => 3 / 4,
                                            'y' => 1 / 4,
                                            'width' => 1 / 6,
                                            'height' => 2 / 6,
                                        ],
                                    ],
                                    'small' => [
                                        'title' => 'Tablet / Smartphone',
                                        'allowedAspectRatios' => [
                                            'sixteen_by_nine' => [
                                                'title' => '16:9',
                                                'value' => 16 / 9,
                                            ],
                                            'four_by_three' => [
                                                'title' => '4:3',
                                                'value' => 4 / 3,
                                            ],
                                            'ultrawide' => [
                                                'title' => '21:9',
                                                'value' => 21 / 9,
                                            ],
                                            'NaN' => [
                                                'title' => 'free',
                                                'value' => 0.0,
                                            ],
                                        ],
                                        'selectedRatio' => 'sixteen_by_nine',
                                        // no cover area by intention
                                        'focusArea' => [
                                            'x' => 1 / 4,
                                            'y' => 1 / 4,
                                            'width' => 3 / 4,
                                            'height' => 3 / 4,
                                        ],
                                    ],
                                    'watch' => [
                                        'title' => 'Smartwatch',
                                        'allowedAspectRatios' => [
                                            'sixteen_by_nine' => [
                                                'title' => '16:9',
                                                'value' => 16 / 9,
                                            ],
                                            'four_by_three' => [
                                                'title' => '4:3',
                                                'value' => 4 / 3,
                                            ],
                                            'ultrawide' => [
                                                'title' => '21:9',
                                                'value' => 21 / 9,
                                            ],
                                            'NaN' => [
                                                'title' => 'free',
                                                'value' => 0.0,
                                            ],
                                        ],
                                        'selectedRatio' => 'sixteen_by_nine',
                                        // no focus area by intention
                                        'coverAreas' => [
                                            [
                                                'x' => 0,
                                                'y' => 0,
                                                'width' => 1,
                                                'height' => 0.25,
                                            ],
                                            [
                                                'x' => 0.08,
                                                'y' => 0.35,
                                                'width' => 0.45,
                                                'height' => 0.5,
                                            ],
                                        ],
                                    ],
                                ],
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
                    group_db_1, crop_1,
                    group_db_2, crop_2, crop_4,
                    group_db_3, crop_3, crop_5, crop_6, crop_7, crop_8, crop_9, crop_10,
                    file_1,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
