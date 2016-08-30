<?php
defined('TYPO3_MODE') or die();

$TCA['tx_blogexample_domain_model_tag'] = [
    'ctrl' => $TCA['tx_blogexample_domain_model_tag']['ctrl'],
    'interface' => [
        'showRecordFieldList' => 'hidden, name, posts'
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0]
                ],
                'default' => 0
            ]
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'foreign_table_where' => 'AND tx_blogexample_domain_model_tag.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_tag.sys_language_uid IN (-1,0)',
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_tag.name',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            ]
        ],
        'posts' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_tag.posts',
            'config' => [
                'type' => 'select',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 9999,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'MM' => 'tx_blogexample_post_tag_mm',
                'MM_opposite_field' => 'tags',
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, name, posts']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
