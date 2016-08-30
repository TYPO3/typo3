<?php
defined('TYPO3_MODE') or die();

$TCA['tx_blogexample_domain_model_post'] = [
    'ctrl' => $TCA['tx_blogexample_domain_model_post']['ctrl'],
    'interface' => [
        'showRecordFieldList' => 'title, date, author',
        'maxDBListItems' => 100,
        'maxSingleDBListItems' => 500
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, blog, title, date, author, content, tags, comments, related_posts']
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
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
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_table_where' => 'AND tx_blogexample_domain_model_post.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_post.sys_language_uid IN (-1,0)',
            ]
        ],
        'l18n_diffsource' => [
            'config'=>[
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'blog' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.blog',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_blogexample_domain_model_blog',
                'maxitems' => 1,
            ]
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            ]
        ],
        'date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.date',
            'config' => [
                'type' => 'input',
                'size' => 12,
                'eval' => 'datetime, required',
                'default' => time()
            ]
        ],
        'author' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.author',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_blogexample_domain_model_person',
                'wizards' => [
                     '_VERTICAL' => 1,
                     'edit' => [
                         'type' => 'popup',
                         'title' => 'Edit',
                         'script' => 'wizard_edit.php',
                         'icon' => 'actions-open',
                         'popup_onlyOpenIfSelected' => 1,
                         'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1',
                     ],
                     'add' => [
                         'type' => 'script',
                         'title' => 'Create new',
                         'icon' => 'actions-add',
                         'params' => [
                             'table'=>'tx_blogexample_domain_model_person',
                             'pid' => '###CURRENT_PID###',
                             'setValue' => 'prepend'
                         ],
                         'script' => 'wizard_add.php',
                     ],
                 ]
            ]
        ],
        'content' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.content',
            'config' => [
                'type' => 'text',
                'rows' => 30,
                'cols' => 80
            ]
        ],
        'tags' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.tags',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_post_tag_mm',
                'maxitems' => 9999,
                'appearance' => [
                    'useCombination' => 1,
                    'useSortable' => 1,
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ]
            ]
        ],
        'comments' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.comments',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_comment',
                'foreign_field' => 'post',
                'size' => 10,
                'maxitems' => 9999,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ]
            ]
        ],
        'related_posts' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.related',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 9999,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_table_where' => 'AND ###THIS_UID### != tx_blogexample_domain_model_post.uid',
                'MM' => 'tx_blogexample_post_post_mm',
                'MM_opposite_field' => 'related_posts',
            ]
        ],
    ]
];
