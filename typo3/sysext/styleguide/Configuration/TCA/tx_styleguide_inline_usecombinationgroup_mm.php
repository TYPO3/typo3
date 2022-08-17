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
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.hidden_toggle',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],

        'group_parent' => [
            'label' => 'group parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombinationgroup',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'group_child' => [
            'label' => 'group child',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_inline_usecombinationgroup_child',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_usecombinationgroup_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_usecombinationgroup_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_usecombinationgroup_mm}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_usecombinationgroup_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_usecombinationgroup_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_usecombinationgroup_mm}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

    ],

    'types' => [
        '1' => [
            'showitem' => 'group_parent, group_child',
        ],
    ],

];
