<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - select foreign single_12',
        'label' => 'fal_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'selicon_field' => 'fal_1',
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
        'fal_1' => [
            'label' => 'fal_1 selicon_field',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
                'relationship' => 'manyToOne',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'fal_1',
        ],
    ],

];
