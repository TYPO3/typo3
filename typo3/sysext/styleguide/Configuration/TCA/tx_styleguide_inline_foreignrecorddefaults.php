<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline foreign_record_defaults',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
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
    ],

    'columns' => [
        'inline_1' => [
            'label' => 'inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_foreignrecorddefaults_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'overrideChildTca' => [
                    'columns' => [
                        'input_1' => [
                            'config' => [
                                'default' => 'default text from parent',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'inline_1',
        ],
    ],

];
