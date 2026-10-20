<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Collection',
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
        'labels' => [
            'label' => 'Labels, MM relation with the foreign side in the labels',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_blogexample_domain_model_label',
                'MM' => 'tx_blogexample_collection_label_mm',
            ],
        ],
        'refs' => [
            'label' => 'References, group field without relation table',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_blogexample_domain_model_label',
                'maxitems' => 5,
            ],
        ],
        'gallery_attachments' => [
            'label' => 'Gallery attachments, inline field with a table field and a match field',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_attachment',
                'foreign_field' => 'parent',
                'foreign_table_field' => 'parent_table',
                'foreign_match_fields' => ['parent_field' => 'gallery_attachments'],
                'foreign_sortby' => 'sorting',
            ],
        ],
        'document_attachments' => [
            'label' => 'Document attachments, inline field with a table field and a match field',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_attachment',
                'foreign_field' => 'parent',
                'foreign_table_field' => 'parent_table',
                'foreign_match_fields' => ['parent_field' => 'document_attachments'],
                'foreign_sortby' => 'sorting',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title, labels, refs, gallery_attachments, document_attachments'],
    ],
];
