<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post',
        'label' => 'title',
        'label_alt' => 'author',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_post.gif'
    ],
    'interface' => [
        'maxDBListItems' => 100,
        'maxSingleDBListItems' => 500
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, blog, title, date, author, second_author, content, tags, comments, related_posts, additional_name, additional_info, additional_comments']
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
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
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
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'blog' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.blog',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_blogexample_domain_model_blog',
                'maxitems' => 1,
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            ]
        ],
        'date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 12,
                'eval' => 'datetime, required',
                'default' => time()
            ]
        ],
        'author' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.author',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_blogexample_domain_model_person',
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                    ],
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'setValue' => 'prepend',
                        ],
                    ],
                ],
            ],
        ],
        'second_author' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.second_author',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_blogexample_domain_model_person',
                'foreign_table' => 'tx_blogexample_domain_model_person',
                'maxitems' => 1,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                    ],
                    'addRecord' => [
                        'disabled' => false,
                    ],
                    'listModule' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'reviewer' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.reviewer',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_blogexample_domain_model_person',
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                    ],
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'setValue' => 'prepend',
                        ],
                    ],
                ],
            ],
        ],
        'content' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.content',
            'config' => [
                'type' => 'text',
                'rows' => 30,
                'cols' => 80
            ]
        ],
        'tags' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.tags',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_post_tag_mm',
                'appearance' => [
                    'useCombination' => 1,
                    'useSortable' => 1,
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ]
            ]
        ],
        'comments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.comments',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_comment',
                'foreign_field' => 'post',
                'size' => 10,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ]
            ]
        ],
        'related_posts' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.related',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_table_where' => 'AND ###THIS_UID### != tx_blogexample_domain_model_post.uid',
                'MM' => 'tx_blogexample_post_post_mm',
                'MM_opposite_field' => 'related_posts',
            ]
        ],
        'additional_name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.additional_name',
            'config' => [
                'type' => 'inline', // this will store the info uid in the additional_name field (CSV)
                'foreign_table' => 'tx_blogexample_domain_model_info',
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'additional_info' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.additional_info',
            'config' => [
                'type' => 'inline', // this will store the post uid in the post field of the info table
                'foreign_table' => 'tx_blogexample_domain_model_info',
                'foreign_field' => 'post',
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'additional_comments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.additional_comments',
            'config' => [
                'type' => 'inline', // this will store the comments uids in the additional_comments field (CSV)
                'foreign_table' => 'tx_blogexample_domain_model_comment',
                'minitems' => 0,
                'maxitems' => 200,
            ],
        ],
    ]
];
