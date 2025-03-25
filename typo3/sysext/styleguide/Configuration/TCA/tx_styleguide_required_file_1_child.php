<?php

return [
    'ctrl' => [
        'title' => 'Form engine - required child file_1',
        'label' => 'file_1',
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
        'file_1' => [
            'label' => 'child file_1 typical fal image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'minitems' => 1,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'description' => 'field description',
                        ],
                    ],
                ],
            ],
        ],
        'file_2' => [
            'label' => 'child file_2 limited amount of files (1 exactly)',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'minitems' => 1,
                'maxitems' => 1,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'description' => 'field description',
                        ],
                    ],
                ],
            ],
        ],
        'file_3' => [
            'label' => 'child file_3 limited amount of files (min 2, max 4)',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'minitems' => 2,
                'maxitems' => 4,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'description' => 'field description',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'file_1, file_2, file_3',
        ],
    ],

];
