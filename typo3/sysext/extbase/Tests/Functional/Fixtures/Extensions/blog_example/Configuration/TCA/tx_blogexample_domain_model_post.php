<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post',
        'label' => 'title',
        'label_alt' => 'author',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_post.gif',
    ],
    'interface' => [
        'maxDBListItems' => 100,
        'maxSingleDBListItems' => 500,
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, blog, title, date, archive_date, author, second_author, content, tags, comments, related_posts, additional_name, additional_info, additional_comments, categories'],
    ],
    'columns' => [
        'categories' => [
            'config' => [
                'type' => 'category',
            ],
        ],
        'blog' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.blog',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_blogexample_domain_model_blog',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.date',
            'config' => [
                'type' => 'datetime',
                'size' => 12,
                'required' => true,
                'default' => time(),
            ],
        ],
        'archive_date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.archive_date',
            'config' => [
                'type' => 'datetime',
                'size' => 12,
            ],
        ],
        'author' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.author',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '--none--', 'value' => 0],
                ],
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
                'default' => 0,
            ],
        ],
        'second_author' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.second_author',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_blogexample_domain_model_person',
                'relationship' => 'manyToOne',
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
                'default' => 0,
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
                'cols' => 80,
            ],
        ],
        'tags' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.tags',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'MM_match_fields' => [
                    'fieldname' => 'tags',
                    'tablenames' => 'tx_blogexample_domain_model_post',
                ],
                'MM_opposite_field' => 'items',
            ],
        ],
        'comments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.comments',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_comment',
                'foreign_field' => 'post',
                'foreign_default_sortby' => 'uid desc',
                'size' => 10,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ],
        ],
        'related_posts' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.related',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_table_where' => 'AND ###THIS_UID### != {#tx_blogexample_domain_model_post}.{#uid}',
                'MM' => 'tx_blogexample_post_post_mm',
            ],
        ],
        'additional_name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.additional_name',
            'config' => [
                'type' => 'inline', // this will store the info uid in the additional_name field (CSV)
                'foreign_table' => 'tx_blogexample_domain_model_info',
                'relationship' => 'manyToOne',
                'default' => 0,
            ],
        ],
        'additional_info' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.additional_info',
            'config' => [
                'type' => 'inline', // this will store the post uid in the post field of the info table
                'foreign_table' => 'tx_blogexample_domain_model_info',
                'foreign_field' => 'post',
                'relationship' => 'manyToOne',
                'default' => 0,
            ],
        ],
        'additional_comments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.additional_comments',
            'config' => [
                'type' => 'inline', // this will store the comments uids in the additional_comments field (CSV)
                'foreign_table' => 'tx_blogexample_domain_model_comment',
                'maxitems' => 200,
            ],
        ],
    ],
];
