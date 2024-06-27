<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn group mm',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'parentid' => [
            'label' => 'parentid',
            'config' => [
                'type' => 'group',
                'size' => 1,
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'allowed' => 'tx_styleguide_inline_mngroup',
                'hideSuggest' => true,
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'childid' => [
            'label' => 'childid',
            'config' => [
                'type' => 'group',
                'size' => 1,
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'allowed' => 'tx_styleguide_inline_mngroup_child',
                'hideSuggest' => true,
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'parentsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'childsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'check_1' => [
            'label' => 'check_1',
            'config' => [
                'type' => 'check',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, parentid, childid, check_1,
                --div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, hotelsort, branchsort',
        ],
    ],

];
