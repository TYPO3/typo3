<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline parent no soft delete',
        'label' => 'file_1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
        'text_1' => [
            'label' => 'text_1',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'file_1' => [
            'label' => 'file_1',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
            ],
        ],
    ],

    'types' => [
        '1' => [
            'showitem' => '
                sys_language_uid, l10n_parent, l10n_diffsource, hidden, text_1, file_1
            ',
        ],
    ],

];
