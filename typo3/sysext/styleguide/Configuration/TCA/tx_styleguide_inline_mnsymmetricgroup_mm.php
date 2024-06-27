<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn symmetric group mm',
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
                'type' => 'group',
                'allowed' => 'tx_styleguide_inline_mnsymmetricgroup',
                'minitems' => 1,
                'relationship' => 'manyToOne',
                'size' => 1,
            ],
        ],
        'branchid' => [
            'label' => 'branchid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_inline_mnsymmetricgroup',
                'minitems' => 1,
                'relationship' => 'manyToOne',
                'size' => 1,
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
