<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_person',
        'label' => 'lastname',
        'label_alt' => 'firstname',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_person.gif'
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_blogexample_domain_model_person',
                'foreign_table_where' => 'AND tx_blogexample_domain_model_person.uid=###REC_FIELD_l10n_parent### AND tx_blogexample_domain_model_person.sys_language_uid IN (-1,0)',
            ]
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'firstname' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_person.firstname',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,required',
                'max' => 256
            ]
        ],
        'lastname' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_person.lastname',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,required',
                'max' => 256
            ]
        ],
        'email' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_person.email',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            ]
        ],
        'tags' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_person.tags',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'MM_match_fields' => [
                    'fieldname' => 'tags'
                ],
                'appearance' => [
                    'useCombination' => 1,
                    'useSortable' => 1,
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ]
            ]
        ],
        'tags_special' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_person.tags_special',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'MM_match_fields' => [
                    'fieldname' => 'tags_special'
                ],
                'appearance' => [
                    'useCombination' => 1,
                    'useSortable' => 1,
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ]
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, firstname, lastname, email, avatar, tags, tags_special']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
