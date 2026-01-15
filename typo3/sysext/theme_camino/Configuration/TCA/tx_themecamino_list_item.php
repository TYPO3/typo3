<?php

return [
    'ctrl' => [
        'title' => 'theme_camino.backend_fields:tx_themecamino_list_item',
        'label' => 'header',
        'label_alt' => 'text,link_label',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'type' => 'uid_foreign:CType',
        'hideTable' => true,
        'sortby' => 'sorting_foreign',
        'delete' => 'deleted',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => ['default' => 'theme-camino-record-listitem'],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        // System fields, might be just passthrough at some point?
        'uid_foreign' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.uid_foreign',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_content',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'sorting_foreign' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.sorting_foreign',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'max' => 4,
                'checkbox' => 0,
                'range' => [
                    'upper' => 1000,
                    'lower' => 10,
                ],
                'default' => 0,
            ],
        ],
        'tablename' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.tablename',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'fieldname' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.fieldname',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],

        // Custom fields
        'category' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.category',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 50,
            ],
        ],
        'date' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'nullable' => true,
            ],
        ],
        'header' => [
            'l10n_mode' => 'prefixLangTitle',
            'l10n_cat' => 'text',
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.header',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 256,
            ],
        ],
        'images' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.images',
            'config' => [
                'type' => 'file',
                'allowed' => ['common-image-types'],
                'appearance' => [
                    'createNewRelationLinkTitle' => 'theme_camino.backend_fields:tx_themecamino_list_item.images.addFileReference',
                    'showPossibleLocalizationRecords' => true,
                ],
            ],
        ],
        'link' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.link',
            'config' => [
                'type' => 'link',
                'size' => 50,
                'appearance' => [
                    'browserTitle' => 'theme_camino.backend_fields:tx_themecamino_list_item.link',
                ],
            ],
        ],
        'link_config' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.link_config',
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

            ],
        ],
        'link_icon' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.link_icon',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [],
            ],
        ],
        'link_label' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.link_label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 256,
            ],
        ],
        'text' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.text',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 10,
                'softref' => 'typolink_tag,images,email[subst],url',
            ],
        ],
    ],
    'palettes' => [
        'linklabel' => [
            'showitem' => 'link, link_label',
        ],
        'linklabelconfig' => [
            'showitem' => 'link, link_label, --linebreak--, link_config',
        ],
        'linklabelicon' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.palettes.linklabelicon',
            'showitem' => 'link, link_label, --linebreak--, link_icon',
        ],
        'linklabeliconconfig' => [
            'label' => 'theme_camino.backend_fields:tx_themecamino_list_item.palettes.linklabeliconconfig',
            'showitem' => 'link, link_label, --linebreak--, link_icon, link_config',
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '',
        ],
    ],
];
