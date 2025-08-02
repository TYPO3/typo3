<?php

return [
    'ctrl' => [
        'title' => 'Record with file fields',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                title,
                file_unrestricted_single, file_image_single, file_app_single, file_extension_single,  file_extensionstorage_single,
                file_unrestricted_multi, file_image_multi, file_app_multi, file_extension_multi, file_extensionstorage_multi,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,

                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    hidden, --palette--;;timeRestriction, fe_group,

                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        'language' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_extbaseupload_domain_model_singlefile',
                'foreign_table_where' => 'AND tx_extbaseupload_domain_model_singlefile.pid=###CURRENT_PID### AND tx_extbaseupload_domain_model_singlefile.sys_language_uid IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => false,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'fe_group' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],

        'file_unrestricted_single' => [
            'exclude' => true,
            'label' => 'Single file (unrestricted)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],
        'file_image_single' => [
            'exclude' => true,
            'label' => 'Single file (image)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],
        'file_app_single' => [
            'exclude' => true,
            'label' => 'Single file (app)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],
        'file_extension_single' => [
            'exclude' => true,
            'label' => 'Single file (extension validator)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],
        'file_extensionstorage_single' => [
            'exclude' => true,
            'label' => 'Single file (extension validator with fallback)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],
        'file_extensionstorageplus_single' => [
            'exclude' => true,
            'label' => 'Single file (extension validator with fallback and extra)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],

        'file_unrestricted_multi' => [
            'exclude' => true,
            'label' => 'Multi file (unrestricted)',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
            ],
        ],
        'file_image_multi' => [
            'exclude' => true,
            'label' => 'Multi file (image)',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
            ],
        ],
        'file_app_multi' => [
            'exclude' => true,
            'label' => 'Multi file (app)',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
            ],
        ],
        'file_extension_multi' => [
            'exclude' => true,
            'label' => 'Multi file (extension validator)',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
            ],
        ],
        'file_extensionstorage_multi' => [
            'exclude' => true,
            'label' => 'Multi file (extension validator with fallback)',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
            ],
        ],
        'file_extensionstorageplus_multi' => [
            'exclude' => true,
            'label' => 'Multi file (extension validator with fallback and extra)',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
            ],
        ],

    ],
];
