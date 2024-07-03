<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline MM',
        'label' => 'title',
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
        'title' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'required' => true,
            ],
        ],
        'inline_1' => [
            'label' => 'inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_mm_child',
                'MM' => 'tx_styleguide_inline_mm_child_rel',
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
                --div--;General, title, inline_1,
                --div--;Visibility, sys_language_uid, l18n_parent,l18n_diffsource, hidden
            ',
        ],
    ],

];
