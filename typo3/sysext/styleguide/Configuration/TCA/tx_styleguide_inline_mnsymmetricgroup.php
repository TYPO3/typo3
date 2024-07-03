<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn symmetric group',
        'label' => 'input_1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'sortby' => 'sorting',
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
        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'required' => true,
            ],
        ],
        'branches' => [
            'label' => 'branches',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_mnsymmetricgroup_mm',
                'foreign_field' => 'hotelid',
                'foreign_sortby' => 'hotelsort',
                'foreign_label' => 'branchid',
                'symmetric_field' => 'branchid',
                'symmetric_sortby' => 'branchsort',
                'symmetric_label' => 'hotelid',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, input_1, branches,
                --div--;Visibility, sys_language_uid, l18n_parent,l18n_diffsource, hidden
            ',
        ],
    ],

];
