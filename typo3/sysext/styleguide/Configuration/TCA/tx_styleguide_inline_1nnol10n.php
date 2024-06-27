<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign field without l10n in child',
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
            'l10n_mode' => 'exclude',
            'label' => 'inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_1nnol10n_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
            'relationship' => 'manyToOne',
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                inline_1,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,

            ',
        ],
    ],

];
