<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_blog',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_blog.gif',
    ],
    'columns' => [
        'categories' => [
            'config' => [
                'type' => 'category',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_blog.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'subtitle' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_post.subtitle',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'nullable' => true,
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_blog.description',
            'config' => [
                'type' => 'text',
                'required' => true,
                'rows' => 30,
                'cols' => 80,
            ],
        ],
        'logo' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_blog.logo',
            'config' => [
                'type' => 'file',
            ],
        ],
        'posts' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_blog.posts',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_field' => 'blog',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ],
        ],
        'administrator' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_blog.administrator',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'foreign_table_where' => "AND {#fe_users}.{#tx_extbase_type}='TYPO3Tests\\\\BlogExample\\\\Domain\\\\Model\\\\Administrator'",
                'items' => [
                    ['label' => '--none--', 'value' => 0],
                ],
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
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, fe_group, title, description, logo, posts, administrator, categories'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
