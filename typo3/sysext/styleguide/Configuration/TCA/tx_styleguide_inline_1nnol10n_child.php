<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign field child without l10n',
        'label' => 'input_1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'enablecolumns' => [
            'disabled' => 'disable',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'versioningWS' => true,
    ],

    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, input_1,
                --div--;meta, disable,
            ',
        ],
    ],

];
