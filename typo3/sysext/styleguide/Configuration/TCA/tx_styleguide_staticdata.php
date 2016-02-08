<?php
return [
    'ctrl' => [
        'title' => 'Form engine - static data',
        'label' => 'value_1',
        'rootLevel' => 1,
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms_staticdata.svg',
    ],

    'columns' => [


        'value_1' => [
            'label' => 'Value',
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
