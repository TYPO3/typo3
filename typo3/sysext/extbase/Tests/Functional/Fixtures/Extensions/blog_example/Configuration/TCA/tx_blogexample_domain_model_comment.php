<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_comment',
        'label' => 'date',
        'label_alt' => 'author',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_comment.gif'
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_comment.date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'dbType' => 'datetime',
                'size' => 12,
                'eval' => 'datetime, required',
                'default' => time()
            ]
        ],
        'author' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_comment.author',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            ]
        ],
        'email' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_comment.email',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            ]
        ],
        'content' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_comment.content',
            'config' => [
                'type' => 'text',
                'rows' => 30,
                'cols' => 80
            ]
        ],
        'post' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, date, author, email, content']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
