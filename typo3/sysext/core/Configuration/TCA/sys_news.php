<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_news',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'adminOnly' => true,
        'rootLevel' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'default_sortby' => 'crdate DESC',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_news'
        ]
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title,content,starttime,endtime'
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.disable',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required'
            ]
        ],
        'content' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.text',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 5,
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ],
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title,content,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden, --palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime']
    ],
];
