<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Label',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_dateexample.gif',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'collections' => [
            'label' => 'Collections, foreign side of the MM relation',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_blogexample_domain_model_collection',
                'MM' => 'tx_blogexample_collection_label_mm',
                'MM_opposite_field' => 'labels',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title, collections'],
    ],
];
