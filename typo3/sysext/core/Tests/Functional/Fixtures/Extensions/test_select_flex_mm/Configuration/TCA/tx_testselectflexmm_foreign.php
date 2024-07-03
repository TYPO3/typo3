<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_select_flex_mm foreign',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_select_flex_mm/Resources/Public/Icons/Extension.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'title' => [
            'label' => 'title',
            'config' => [
                'type' => 'input',
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;title,
                    title,
                --div--;meta,
                    sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
