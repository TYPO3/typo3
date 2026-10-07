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
        'shared_mm_primary_records' => [
            'label' => 'shared_mm_primary_records MM_oppositeUsage',
            'description' => 'Reverse side of shared_mm_primary in the select showcase',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_elements_select',
                'MM' => 'tx_styleguide_staticdata_shared_mm',
                'MM_match_fields' => [
                    'fieldname' => 'shared_mm_primary',
                    'tablenames' => 'tx_styleguide_elements_select',
                ],
                'MM_oppositeUsage' => [
                    'tx_styleguide_elements_select' => ['shared_mm_primary'],
                ],
            ],
        ],
        'shared_mm_secondary_records' => [
            'label' => 'shared_mm_secondary_records MM_oppositeUsage',
            'description' => 'Reverse side of shared_mm_secondary in the select showcase',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_elements_select',
                'MM' => 'tx_styleguide_staticdata_shared_mm',
                'MM_match_fields' => [
                    'fieldname' => 'shared_mm_secondary',
                    'tablenames' => 'tx_styleguide_elements_select',
                ],
                'MM_oppositeUsage' => [
                    'tx_styleguide_elements_select' => ['shared_mm_secondary'],
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'value_1, shared_mm_primary_records, shared_mm_secondary_records',
        ],
    ],

];
