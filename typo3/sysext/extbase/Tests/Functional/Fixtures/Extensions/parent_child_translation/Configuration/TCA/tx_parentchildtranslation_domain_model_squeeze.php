<?php

return [
    'ctrl' => [
        'title' => 'Squeeze',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title, child,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden',
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'child' => [
            'exclude' => true,
            'label' => 'Child',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_parentchildtranslation_domain_model_child',
                'foreign_table_where' => 'AND {#tx_parentchildtranslation_domain_model_child}.{#sys_language_uid} IN (0,-1)',
                'default' => 0,
            ],
        ],
        'parent' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
