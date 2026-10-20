<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Folder',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'sortby' => 'sorting',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'children' => [
            'label' => 'Children',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_folder',
                'foreign_field' => 'parent',
                'foreign_sortby' => 'sorting',
            ],
        ],
        'parent' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, l18n_parent, title, children'],
    ],
];
