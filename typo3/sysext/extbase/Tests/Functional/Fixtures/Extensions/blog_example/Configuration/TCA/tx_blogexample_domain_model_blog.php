<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'versioning_followPages' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'fe_group' => 'fe_group',
        ),
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_blog.gif'
    ),
    'interface' => array(
        'showRecordFieldList' => 'title, posts, administrator'
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
                ),
                'default' => 0
            )
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_blogexample_domain_model_blog',
                'foreign_table_where' => 'AND tx_blogexample_domain_model_blog.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_blog.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => array(
            'config'=>array(
                'type' => 'passthrough',
                'default' => ''
            )
        ),
        't3ver_label' => array(
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type'=>'none',
                'cols' => 27
            )
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check'
            )
        ),
        'fe_group' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => array(
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login',
                        -1,
                    ),
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                        -2,
                    ),
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                        '--div--',
                    ),
                ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ),
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.title',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,required',
                'max' => 256
            )
        ),
        'description' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.description',
            'config' => array(
                'type' => 'text',
                'eval' => 'required',
                'rows' => 30,
                'cols' => 80,
            )
        ),
        'logo' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.logo',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 3000,
                'uploadfolder' => 'uploads/pics',
                'show_thumbs' => 1,
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0
            )
        ),
        'posts' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.posts',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_field' => 'blog',
                'foreign_sortby' => 'sorting',
                'maxitems' => 999999,
                'appearance' => array(
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ),
            )
        ),
        'administrator' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.administrator',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'foreign_table_where' => "AND fe_users.tx_extbase_type='Tx_BlogExample_Domain_Model_Administrator'",
                'items' => array(
                    array('--none--', 0),
                ),
                'wizards' => array(
                     '_VERTICAL' => 1,
                     'edit' => array(
                         'type' => 'popup',
                         'title' => 'Edit',
                         'module' => array(
                             'name' => 'wizard_edit',
                         ),
                         'icon' => 'actions-open',
                         'popup_onlyOpenIfSelected' => 1,
                         'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1',
                     ),
                     'add' => array(
                         'type' => 'script',
                         'title' => 'Create new',
                         'icon' => 'actions-add',
                         'params' => array(
                             'table'=>'fe_users',
                             'pid' => '###CURRENT_PID###',
                             'setValue' => 'prepend'
                         ),
                         'module' => array(
                             'name' => 'wizard_add',
                         ),
                     ),
                 )
            )
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'sys_language_uid, hidden, fe_group, title, description, logo, posts, administrator')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
