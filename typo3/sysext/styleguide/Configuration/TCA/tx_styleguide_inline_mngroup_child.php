<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn group child',
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
        'parents' => [
            'label' => 'parents',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_mngroup_mm',
                'foreign_field' => 'childid',
                'foreign_sortby' => 'childsort',
                'foreign_label' => 'parentid',
                'foreign_unique' => 'parentid',
                'foreign_selector' => 'parentid',
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
                --div--;General, input_1, parents,
                --div--;Visibility, sys_language_uid, l18n_parent,l18n_diffsource, hidden
            ',
        ],
    ],

];
