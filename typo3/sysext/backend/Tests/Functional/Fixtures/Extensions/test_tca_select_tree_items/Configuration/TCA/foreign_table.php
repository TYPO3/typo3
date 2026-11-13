<?php

return [
    'ctrl' => [
        'title' => 'A foreign table',
        'label' => 'title',
        'rootLevel' => -1,
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'children_field' => [
            'label' => 'Children Field',
            'config' => [
                'type' => 'input',
            ],
        ],
        'parents' => [
            'label' => 'Parents',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'foreign_table',
                'maxitems' => 99,
                'treeConfig' => [
                    'parentField' => 'parents',
                ],
            ],
        ],
        'parent_as_string' => [
            'label' => 'Parent stored in a string column',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'foreign_table',
                'relationship' => 'manyToOne',
                'maxitems' => 1,
                'treeConfig' => [
                    'parentField' => 'parent_as_string',
                ],
            ],
        ],
    ],
];
