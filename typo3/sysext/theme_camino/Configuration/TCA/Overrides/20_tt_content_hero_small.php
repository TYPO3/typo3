<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_hero_small.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_hero_small.description',
        'value' => 'camino_hero_small',
        'icon' => 'content-header',
        'group' => 'camino_hero',
    ],
    '
            --palette--;;headers,
            --palette--;;camino_linklabeliconconfig,
        --div--;core.form.tabs:images,
            image,
    ',
    [
        'columnsOverrides' => [
            'image' => [
                'config' => [
                    'overrideChildTca' => [
                        'columns' => [
                            'crop' => [
                                'config' => [
                                    'cropVariants' => [
                                        'default' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.default',
                                            'allowedAspectRatios' => [
                                                '16:9' => [
                                                    'title' => '16:9',
                                                    'value' => 1.77,
                                                ],
                                            ],
                                        ],
                                        'sm' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.sm',
                                            'allowedAspectRatios' => [
                                                '16:15' => [
                                                    'title' => '16:15',
                                                    'value' => 1.066,
                                                ],
                                            ],
                                        ],
                                        'md' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.md',
                                            'allowedAspectRatios' => [
                                                '4:3' => [
                                                    'title' => '4:3',
                                                    'value' => 1.333,
                                                ],
                                            ],
                                        ],
                                        'lg' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.lg',
                                            'allowedAspectRatios' => [
                                                '3:2' => [
                                                    'title' => '3:2',
                                                    'value' => 1.5,
                                                ],
                                            ],
                                        ],
                                        'xl' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.xl',
                                            'allowedAspectRatios' => [
                                                '2:1' => [
                                                    'title' => '2:1',
                                                    'value' => 2,
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
    ],
);
