<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign_match_fields',
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
            'label' => 'inline_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_1nreusabletable_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_match_fields' => [
                    'role' => 'inline_1',
                ],
            ],
        ],
        'inline_2' => [
            'label' => 'inline_2',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_1nreusabletable_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_match_fields' => [
                    'role' => 'inline_2',
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;inline_1,
                    inline_1,
                --div--;inline_2,
                    inline_2,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,

            ',
        ],
    ],

];
