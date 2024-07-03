<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline expand',
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
                'foreign_table' => 'tx_styleguide_inline_expand_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'appearance' => [
                    'collapseAll' => false,
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                inline_1
            ',
        ],
    ],

];
