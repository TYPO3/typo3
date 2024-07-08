<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline with child overrideChildTca child',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
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
        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'config' => [
                'type' => 'text',
                'rows' => 15,
                'cols' => 80,
                'enableRichtext' => false,
                'richtextConfiguration' => 'none',
            ],
        ],
        'input_2' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_2',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 40,
                'enableRichtext' => false,
                'richtextConfiguration' => 'default',
            ],
        ],
        'role' => [
            'label' => 'role (used for inline relation, non-passthrough to force field auto-creation)',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'input_1, input_2',
        ],
    ],

];
