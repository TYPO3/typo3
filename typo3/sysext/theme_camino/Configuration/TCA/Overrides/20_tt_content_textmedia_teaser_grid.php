<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_textmedia_teaser_grid.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_textmedia_teaser_grid.description',
        'value' => 'camino_textmedia_teaser_grid',
        'icon' => 'content-container-columns-3',
        'group' => 'camino_teaser',
    ],
    '
        --palette--;;headers,
        bodytext,
        --palette--;;camino_linklabeliconconfig,
        --div--;theme_camino.backend_fields:tt_content.tabs.teaser,
        tx_themecamino_list_elements,
        --div--;core.form.tabs:appearance,
        --palette--;;frames
    ',
    [
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
                ],
            ],
            'tx_themecamino_list_elements' => [
                'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_list_elements.types.camino_textmedia_teaser_grid.label',
                'config' => [
                    'overrideChildTca' => [
                        'columns' => [
                            'text' => [
                                'config' => [
                                    'rows' => 3,
                                ],
                            ],
                            'images' => [
                                'config' => [
                                    'overrideChildTca' => [
                                        'columns' => [
                                            'crop' => [
                                                'config' => [
                                                    'cropVariants' => [
                                                        'default' =>  [
                                                            'title' => 'theme_camino.backend_fields:cropVariants.default',
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
                        'types' => [
                            '0' => [
                                'showitem' => 'header,
                                --palette--;;date_category,
                                text,
                                images,
                                --palette--;;linklabelicon',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$GLOBALS['TCA']['tx_themecamino_list_item']['palettes']['date_category'] = [
    'showitem' => 'date, category',
];
