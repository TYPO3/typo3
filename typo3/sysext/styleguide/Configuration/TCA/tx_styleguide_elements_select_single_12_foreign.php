<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - select foreign single_12',
        'label' => 'fal_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
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
    ],

    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_select_single_12_foreign}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_select_single_12_foreign}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'exclude' => true,
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
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_select_single_12_foreign}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_select_single_12_foreign}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],

        'fal_1' => [
            'label' => 'fal_1 selicon_field',
            'exclude' => 1,
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'fal_1',
                [
                    'maxitems' => 1,
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            ),
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => 'fal_1',
        ],
    ],

];
