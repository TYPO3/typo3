<?php
defined('TYPO3_MODE') or die();

$TCA['tx_blogexample_domain_model_blog'] = [
    'ctrl' => $TCA['tx_blogexample_domain_model_blog']['ctrl'],
    'interface' => [
        'showRecordFieldList' => 'title, posts, administrator'
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
                'foreign_table' => 'tx_blogexample_domain_model_blog',
                'foreign_table_where' => 'AND tx_blogexample_domain_model_blog.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_blog.sys_language_uid IN (-1,0)',
            ]
        ],
        'l18n_diffsource' => [
            'config'=>[
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        't3ver_label' => [
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type'=>'none',
                'cols' => 27
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login',
                        -1,
                    ],
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                        -2,
                    ],
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                        '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,required',
                'max' => 256
            ]
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.description',
            'config' => [
                'type' => 'text',
                'eval' => 'required',
                'rows' => 30,
                'cols' => 80,
            ]
        ],
        'logo' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.logo',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 3000,
                'uploadfolder' => 'uploads/pics',
                'show_thumbs' => 1,
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0
            ]
        ],
        'posts' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.posts',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_field' => 'blog',
                'foreign_sortby' => 'sorting',
                'maxitems' => 999999,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ]
        ],
        'administrator' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.administrator',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'foreign_table_where' => "AND fe_users.tx_extbase_type='Tx_BlogExample_Domain_Model_Administrator'",
                'items' => [
                    ['--none--', 0],
                    ],
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
                             'table'=>'fe_users',
                             'pid' => '###CURRENT_PID###',
                             'setValue' => 'prepend'
                         ],
                         'script' => 'wizard_add.php',
                     ],
                 ]
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, fe_group, title, description, logo, posts, administrator']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
