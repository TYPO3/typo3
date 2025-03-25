<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - t3editor child inline_1',
        'label' => 't3editor_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
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
        'versioningWS' => true,
    ],

    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3editor_1' => [
            'label' => 't3editor_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'text',
                'renderType' => 'codeEditor',
                'format' => 'html',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 't3editor_1',
        ],
    ],

];
