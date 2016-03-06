<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment',
        'label' => 'date',
        'label_alt' => 'author',
        'label_alt_force' => true,
        'tstamp'   => 'tstamp',
        'crdate'   => 'crdate',
        'delete'   => 'deleted',
        'enablecolumns'  => array(
            'disabled' => 'hidden'
        ),
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_comment.gif'
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden, date, author, email, content'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check'
            )
        ),
        'date' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.date',
            'config' => array(
                'type' => 'input',
                'dbType' => 'datetime',
                'size' => 12,
                'eval' => 'datetime, required',
                'default' => time()
            )
        ),
        'author' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.author',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            )
        ),
        'email' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.email',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            )
        ),
        'content' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.content',
            'config' => array(
                'type' => 'text',
                'rows' => 30,
                'cols' => 80
            )
        ),
        'post' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'hidden, date, author, email, content')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
