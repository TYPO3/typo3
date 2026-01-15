<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_hero.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_hero.description',
        'value' => 'camino_hero',
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
                                                '4:5' => [
                                                    'title' => '4:5',
                                                    'value' => 0.8,
                                                ],
                                            ],
                                        ],
                                        'md' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.md',
                                            'allowedAspectRatios' => [
                                                '1:1' => [
                                                    'title' => '1:1',
                                                    'value' => 1,
                                                ],
                                            ],
                                        ],
                                        'lg' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.lg',
                                            'allowedAspectRatios' => [
                                                '9:8' => [
                                                    'title' => '9:8',
                                                    'value' => 1.125,
                                                ],
                                            ],
                                        ],
                                        'xl' =>  [
                                            'title' => 'theme_camino.backend_fields:cropVariants.xl',
                                            'allowedAspectRatios' => [
                                                '3:2' => [
                                                    'title' => '3:2',
                                                    'value' => 1.5,
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
