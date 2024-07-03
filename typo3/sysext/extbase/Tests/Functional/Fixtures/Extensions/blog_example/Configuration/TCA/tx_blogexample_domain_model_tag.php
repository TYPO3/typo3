<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_tag',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_tag.gif',
    ],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_tag.name',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'items' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_tag.items',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_blogexample_domain_model_person,tx_blogexample_domain_model_post',
                'size' => 10,
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'MM_oppositeUsage' => [
                    'tx_blogexample_domain_model_person' => [
                        'tags',
                        'tags_special',
                    ],
                    'tx_blogexample_domain_model_post' => [
                        'tags',
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, name, items'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
