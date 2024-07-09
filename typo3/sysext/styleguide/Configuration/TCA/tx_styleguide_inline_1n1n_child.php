<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n 1:n child',
        'label' => 'inline_1',
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
        'inline_1' => [
            'label' => 'inline_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_1n1n_childchild',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],
        'inline_2' => [
            'label' => 'inline_2',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:styleguide/Configuration/FlexForms/SimpleSection.xml',
                ],
            ],
        ],
        'inline_3' => [
            'label' => 'inline_3',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:styleguide/Configuration/FlexForms/MultipleSheets.xml',
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                inline_1,inline_2,inline_3,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
