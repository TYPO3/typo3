<?php

return [
    'ctrl' => [
        'title' => 'TCA Select Tree Items',
        'label' => 'select_tree',
    ],
    'columns' => [
        'select_tree' => [
            'label' => 'Select Tree',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'treeConfig' => [
                    'childrenField' => 'children_field',
                ],
                'foreign_table' => 'foreign_table',
                'items' => [],
                'maxitems' => 1,
            ],
        ],
    ],
];
