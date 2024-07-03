<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline foreign_record_defaults child',
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
                'type' => 'input',
                'size' => '30',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'input_1',
        ],
    ],

];
