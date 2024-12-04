<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_restrictedcomment',
        'label' => 'content',
        'tstamp' => 'customtstamp',
        'crdate' => 'customcrdate',
        'delete' => 'customdeleted',
        'languageField' => 'customsyslanguageuid',
        'translationSource' => 'custom_l10182342n_source',
        'transOrigPointerField' => 'custom_l10182342n_parent',
        'transOrigDiffSourceField' => 'custom_l10182342n_diff',
        'type' => 'custom_ctype',
        'enablecolumns' => [
            'disabled' => 'customhidden',
            'starttime' => 'customstarttime',
            'endtime' => 'customendtime',
            'fe_group' => 'customfegroup',
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_comment.gif',
    ],
    'columns' => [
        'content' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_comment.content',
            'config' => [
                'type' => 'text',
                'rows' => 30,
                'cols' => 80,
            ],
        ],
        'custom_ctype' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'customhidden, customstartime, customendtime, customfegroup, content'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
