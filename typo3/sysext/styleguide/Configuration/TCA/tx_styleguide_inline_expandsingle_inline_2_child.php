<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline expand single - inline_2',
        'label' => 'uid',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
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

        'inputdatetime_1' => [
            'label' => 'inputdatetime_1',
            'description' => 'format=date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
            ],
        ],
        'inputdatetime_2' => [
            'label' => 'inputdatetime_2',
            'description' => 'dbType=date format=date',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'date',
                'format' => 'date',
            ],
        ],
        'inputdatetime_3' => [
            'label' => 'inputdatetime_3',
            'description' => 'format=datetime eval=datetime',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;tab1,
                    inputdatetime_1, inputdatetime_2,
                --div--;tab2,
                    inputdatetime_3,
            ',
        ],

    ],

];
