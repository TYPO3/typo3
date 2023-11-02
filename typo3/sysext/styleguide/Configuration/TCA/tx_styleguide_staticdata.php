<?php

return [
    'ctrl' => [
        'title' => 'Form engine - static data',
        'label' => 'value_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [

        'value_1' => [
            'label' => 'value_1',
            'config' => [
                'type' => 'input',
                'size' => 10,
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => 'value_1',
        ],
    ],

];
