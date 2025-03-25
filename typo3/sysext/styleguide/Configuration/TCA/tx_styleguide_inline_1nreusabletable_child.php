<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign field child, which is reused in multiple fields',
        'label' => 'email',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'disable',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'versioningWS' => true,
    ],

    'columns' => [
        'email' => [
            'label' => 'email',
            'config' => [
                'type' => 'email',
            ],
        ],
        'role' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, email,
                --div--;meta, disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],
];
