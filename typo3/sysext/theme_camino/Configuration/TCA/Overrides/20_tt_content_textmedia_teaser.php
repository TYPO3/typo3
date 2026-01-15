<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_textmedia_teaser.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_textmedia_teaser.description',
        'value' => 'camino_textmedia_teaser',
        'icon' => 'content-container-columns-1',
        'group' => 'camino_teaser',
    ],
    '
            --palette--;;headers,
            bodytext,
            --palette--;;camino_linklabelicon,
        --div--;core.form.tabs:images,
            image,
    ',
    [
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
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
            'subheader' => [
                'label' => 'theme_camino.backend_fields:tt_content.subheader.types.camino_textmedia_teaser.label',
            ],
        ],
    ],
);
