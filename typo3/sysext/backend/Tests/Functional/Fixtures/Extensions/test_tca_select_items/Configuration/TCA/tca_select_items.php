<?php

return [
    'ctrl' => [
        'title' => 'TCA Select Items',
        'label' => 'rowField',
    ],
    'columns' => [
        'rowField' => [
            'label' => 'Row field',
            'config' => [
                'type' => 'input',
            ],
        ],
        'rowFieldTwo' => [
            'label' => 'Row field two',
            'config' => [
                'type' => 'input',
            ],
        ],
        'mm_field' => [
            'label' => 'MM field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'maxitems' => 999,
                'foreign_table' => 'foreign_table',
                'MM' => 'select_ftable_mm',
                'items' => [],
            ],
        ],
        'foreign_field' => [
            'label' => 'Foreign field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'maxitems' => 999,
                'foreign_table' => 'foreign_table',
                'items' => [],
            ],
        ],
    ],
];
