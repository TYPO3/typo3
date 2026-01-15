<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Add item groups for field 'CType'
ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'camino_hero',
    'theme_camino.backend_fields:tt_content.group.camino_hero',
    'before:default'
);
ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'camino_teaser',
    'theme_camino.backend_fields:tt_content.group.camino_teaser',
    'after:default'
);

$additionalColumns = [
    // add field for saving the list of elements for a list or a slider
    'tx_themecamino_list_elements' => [
        'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_list_elements',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_themecamino_list_item',
            'foreign_field' => 'uid_foreign',
            'foreign_table_field' => 'tablename',
            'foreign_match_fields' => [
                'fieldname' => 'tx_themecamino_list_elements',
            ],
            'appearance' => [
                'showSynchronizationLink' => false,
                'showAllLocalizationLink' => true,
                'showPossibleLocalizationRecords' => true,
                'expandSingle' => true,
                'newRecordLinkAddTitle' => false,
                'newRecordLinkTitle' => 'theme_camino.backend_fields:tt_content.tx_themecamino_list_elements.appearance.newRecordLinkTitle',
                'useSortable' => true,
                'useCombination' => false,
            ],
        ],
    ],

    // link fields
    'tx_themecamino_link' => [
        'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link',
        'config' => [
            'type' => 'link',
            'size' => 30,
            'appearance' => [
                'browserTitle' => 'frontend.ttc:header_link_formlabel',
            ],
        ],
    ],
    'tx_themecamino_link_label' => [
        'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_label',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'max' => 256,
        ],
    ],
    'tx_themecamino_link_config' => [
        'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.0',
                    'value' => '0',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.secondary',
                    'value' => 'secondary',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.soft',
                    'value' => 'soft',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.text',
                    'value' => 'text',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.inverted',
                    'value' => 'inverted',
                    'group' => 'inverted',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.inverted-secondary',
                    'value' => 'inverted-secondary',
                    'group' => 'inverted',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.inverted-soft',
                    'value' => 'inverted-soft',
                    'group' => 'inverted',
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.I.inverted-text',
                    'value' => 'inverted-text',
                    'group' => 'inverted',
                ],
            ],
            'itemGroups' => [
                'inverted' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_config.itemgroup.inverted',
            ],
        ],
    ],
    'tx_themecamino_link_icon' => [
        'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_link_icon',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [],
        ],
    ],

    'tx_themecamino_header_style' => [
        'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_header_style',
        'config' => [
            'default' => 0,
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_header_style.option.default',
                    'value' => 0,
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_header_style.option.large',
                    'value' => 1,
                ],
                [
                    'label' => 'theme_camino.backend_fields:tt_content.tx_themecamino_header_style.option.small',
                    'value' => 2,
                ],
            ],
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('tt_content', $additionalColumns);

$GLOBALS['TCA']['tt_content']['palettes']['camino_linklabel'] = [
    'label' => 'theme_camino.backend_fields:tt_content.palettes.camino_linklabel',
    'showitem' => 'tx_themecamino_link, tx_themecamino_link_label',
];

$GLOBALS['TCA']['tt_content']['palettes']['camino_linklabelicon'] = [
    'label' => 'theme_camino.backend_fields:tt_content.palettes.camino_linklabelicon',
    'showitem' => 'tx_themecamino_link, tx_themecamino_link_label, --linebreak--, tx_themecamino_link_icon',
];

$GLOBALS['TCA']['tt_content']['palettes']['camino_linklabelconfig'] = [
    'label' => 'theme_camino.backend_fields:tt_content.palettes.camino_linklabelconfig',
    'showitem' => 'tx_themecamino_link, tx_themecamino_link_label, --linebreak--, tx_themecamino_link_config',
];

$GLOBALS['TCA']['tt_content']['palettes']['camino_linklabeliconconfig'] = [
    'label' => 'theme_camino.backend_fields:tt_content.palettes.camino_linklabeliconconfig',
    'showitem' => 'tx_themecamino_link, tx_themecamino_link_label, --linebreak--, tx_themecamino_link_icon, tx_themecamino_link_config',
];

// add link palette to CTypes
ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--palette--;;camino_linklabeliconconfig',
    'text,textmedia,textpic',
    'after:bodytext',
);

// add header types select to header palette
ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'header',
    'tx_themecamino_header_style',
    'after:header_layout'
);
ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'headers',
    'tx_themecamino_header_style',
    'after:header_layout'
);

$GLOBALS['TCA']['tt_content']['palettes']['camino_person'] = [
    'label' => 'theme_camino.backend_fields:tt_content.palettes.camino_person',
    'showitem' => 'header, --linebreak--, subheader, --linebreak--, image',
];
