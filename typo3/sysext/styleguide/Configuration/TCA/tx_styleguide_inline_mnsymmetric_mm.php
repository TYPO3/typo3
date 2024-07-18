<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn symmetric select mm',
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
        'hotelid' => [
            'label' => 'hotelid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mnsymmetric',
                'localizeReferences' => 1,
            ],
        ],
        'branchid' => [
            'label' => 'branchid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mnsymmetric',
                'localizeReferences' => 1,
            ],
        ],
        'hotelsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'branchsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, title, hotelid, branchid,
                --div--;Visibility, sys_language_uid, l18n_parent, l10n_diffsource, hidden, hotelsort, branchsort',
        ],
    ],

];
