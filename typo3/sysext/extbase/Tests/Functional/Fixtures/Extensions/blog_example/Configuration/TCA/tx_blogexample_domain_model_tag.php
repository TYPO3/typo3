<?php

return array(
    'ctrl' => array(
        'title'    => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_tag',
        'label' => 'name',
        'tstamp'   => 'tstamp',
        'crdate'   => 'crdate',
        'delete'   => 'deleted',
        'enablecolumns'  => array(
            'disabled' => 'hidden'
        ),
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_tag.gif'
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden, name, posts'
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
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'foreign_table_where' => 'AND tx_blogexample_domain_model_tag.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_tag.sys_language_uid IN (-1,0)',
            )
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check'
            )
        ),
        'name' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_tag.name',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            )
        ),
        'posts' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_tag.posts',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 9999,
                'autoSizeMax' => 30,
                'multiple' => 0,
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'MM' => 'tx_blogexample_post_tag_mm',
                'MM_opposite_field' => 'tags',
            )
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'sys_language_uid, hidden, name, posts')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
