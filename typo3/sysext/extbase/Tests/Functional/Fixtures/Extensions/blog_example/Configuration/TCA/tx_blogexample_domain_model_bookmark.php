<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Bookmark',
        'label' => 'title',
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_dateexample.gif',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'tags' => [
            'exclude' => true,
            'label' => 'Tags, MM relation of a table without delete column',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'MM_match_fields' => [
                    'fieldname' => 'tags',
                    'tablenames' => 'tx_blogexample_domain_model_bookmark',
                ],
                'MM_opposite_field' => 'items',
            ],
        ],
        'posts' => [
            'exclude' => true,
            'label' => 'Posts, inline relation without cascade remove',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_post',
                'foreign_field' => 'bookmark',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title, tags, posts'],
    ],
];
