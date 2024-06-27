<?php

return [
    'ctrl' => [
        'title'    => 'Form engine - inline use combination group mm',
        'label' => 'group_child',
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
        'group_parent' => [
            'label' => 'group parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombinationgroup',
                'minitems' => 1,
            ],
        ],
        'group_child' => [
            'label' => 'group child',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_inline_usecombinationgroup_child',
                'size' => 1,
                'minitems' => 1,
                'relationship' => 'manyToOne',
            ],
        ],
    ],

    'types' => [
        '1' => [
            'showitem' => 'group_parent, group_child',
        ],
    ],

];
