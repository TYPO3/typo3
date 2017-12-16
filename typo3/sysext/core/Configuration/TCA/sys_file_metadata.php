<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata',
        'label' => 'file',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'file:type',
        'hideTable' => true,
        'rootLevel' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'default_sortby' => 'crdate DESC',
        'typeicon_classes' => [
            'default' => 'mimetypes-other-other'
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'file,title,description,alternative'
    ],
    'interface' => [
        'showRecordFieldList' => 'file, title, description, alternative'
    ],
    'columns' => [
        'sys_language_uid' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'sys_file_metadata',
                'foreign_table_where' => 'AND sys_file_metadata.uid=###REC_FIELD_l10n_parent### AND sys_file_metadata.sys_language_uid IN (-1,0)',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 30
            ]
        ],
        'fileinfo' => [
            'config' => [
                'type' => 'user',
                'renderType' => 'fileInfo',
            ]
        ],
        'file' => [
            'displayCond' => 'FIELD:sys_language_uid:=:0',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file',
                'foreign_table_where' => 'AND sys_file.uid = ###REC_FIELD_file###',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0,
            ]
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file.title',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'placeholder' => '__row|file|name'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file.description',
            'l10n_mode' => 'prefixLangTitle',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3
            ]
        ],
        'alternative' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file.alternative',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ]
        ],
        'width' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:file.width',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 20,
                'eval' => 'int',
                'default' => 0,
                'readOnly' => true,
            ],
        ],
        'height' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:file.height',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 20,
                'eval' => 'int',
                'default' => 0,
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                fileinfo, title, description, alternative, --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ']
    ],
    'palettes' => [
        'language' => [
            'showitem' => 'sys_language_uid, l10n_parent',
            'isHiddenPalette' => true,
        ],
    ]
];
