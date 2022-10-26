<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - slugs',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
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

        'hidden' => [
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Disable'],
                ],
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_rte',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_rte}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_rte}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_rte',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_rte}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_rte}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],

        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'description' => 'field description',
            'config' => [
                'type' => 'input',
            ],
        ],

        'input_2' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_2',
            'description' => 'field description',
            'config' => [
                'type' => 'input',
            ],
        ],

        'slug_1' => [
            'label' => 'slug_1',
            'description' => 'field description',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['input_1', 'input_2'],
                    'fieldSeparator' => '/',
                    'prefixParentPageSlug' => true,
                    'replacements' => [
                        '/' => '',
                    ],
                ],
                'appearance' => [
                    'prefix' => \TYPO3\CMS\Styleguide\UserFunctions\FormEngine\SlugPrefix::class . '->getPrefix',
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],
        'slug_2' => [
            'label' => 'slug_2',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['input_1'],
                    'fieldSeparator' => '/',
                    'prefixParentPageSlug' => true,
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],
        'slug_4' => [
            'label' => 'slug_4',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['input_1', 'input_2'],
                    'prefixParentPageSlug' => false,
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],
        'slug_5' => [
            'label' => 'slug_5',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => [['input_1', 'input_2']],
                    'prefixParentPageSlug' => false,
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],

        'input_3' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_2',
            'description' => 'field description',
            'config' => [
                'type' => 'input',
                'default' => 'Some Job in city1/city2 (f/m)',
            ],
        ],
        'slug_3' => [
            'label' => 'slug_3',
            'description' => 'remove string (f/m)',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['input_3'],
                    'replacements' => [
                        '(f/m)' => '',
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'prependSlash' => true,
                'eval' => 'uniqueInPid',
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;slug,
                    input_1, input_2, slug_1, slug_2, slug_4, slug_5, input_3, slug_3,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
