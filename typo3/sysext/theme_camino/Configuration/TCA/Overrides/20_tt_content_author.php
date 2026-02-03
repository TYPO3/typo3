<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_author.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_author.description',
        'value' => 'camino_author',
        'icon' => 'content-user',
        'group' => 'default',
    ],
    '
        --palette--;;camino_person,
        bodytext,
        tx_themecamino_list_elements
    ',
    [
        'columnsOverrides' => [
            'header' => [
                'label' => 'theme_camino.backend_fields:tt_content.header.types.person-palette.label',
                'config' => [
                    'required' => true,
                ],
            ],
            'subheader' => [
                'label' => 'theme_camino.backend_fields:tt_content.subheader.types.person-palette.label',
            ],
            'bodytext' => [
                'label' => 'theme_camino.backend_fields:tt_content.bodytext.types.camino_author.label',
                'config' => [
                    'rows' => 3,
                ],
            ],
            'tx_themecamino_list_elements' => [
                'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_list_elements.types.camino_author.label',
                'config' => [
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '--palette--;;linklabel',
                            ],
                        ],
                        'columns' => [
                            'link' => [
                                'config' => [
                                    'required' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
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
                                                '1:1' => [
                                                    'title' => '1:1',
                                                    'value' => 1,
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
