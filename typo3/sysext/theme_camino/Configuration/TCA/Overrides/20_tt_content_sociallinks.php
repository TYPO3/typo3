<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_sociallinks.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_sociallinks.description',
        'value' => 'camino_sociallinks',
        'icon' => 'theme-camino-content-socialmedia',
        'group' => 'special',
    ],
    '
        header,
        tx_themecamino_list_elements,
        ',
    [
        'columnsOverrides' => [
            'header' => [
                'label' => 'theme_camino.backend_fields:tt_content.header.label.ALT',
                'description' => 'theme_camino.backend_fields:tt_content.header.description.ALT',
            ],
            'tx_themecamino_list_elements' => [
                'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_list_elements.types.camino_sociallinks.label',
                'description' => 'theme_camino.backend_fields:tt_content.tx_themecamino_list_elements.types.camino_sociallinks.description',
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
                                    'allowedTypes' => ['url', 'email', 'telephone'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
);
