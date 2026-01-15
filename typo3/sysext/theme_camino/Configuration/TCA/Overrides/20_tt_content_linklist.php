<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'theme_camino.backend_fields:tt_content.CType.camino_linklist.label',
        'description' => 'theme_camino.backend_fields:tt_content.CType.camino_linklist.description',
        'value' => 'camino_linklist',
        'icon' => 'content-bullets',
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
                'config' => [
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '--palette--;;linklabel',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
);
