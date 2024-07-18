<?php

return [
    'ctrl' => [
        'title'    => 'Form engine - inline use combination box mm',
        'label' => 'select_child',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'select_parent' => [
            'label' => 'select parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombinationbox',
                'minitems' => 1,
            ],
        ],
        'select_child' => [
            'label' => 'select child',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombinationbox_child',
                'minitems' => 1,
            ],
        ],
    ],

    'types' => [
        '1' => [
            'showitem' => 'select_parent, select_child',
        ],
    ],

];
