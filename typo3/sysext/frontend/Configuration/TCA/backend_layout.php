<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout',
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'groupName' => 'system',
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-backend_layout',
        ],
        'selicon_field' => 'icon',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.title',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'max' => 255,
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 25,
            ],
        ],
        'config' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.config',
            'config' => [
                'type' => 'text',
                'renderType' => 'belayoutwizard',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'icon' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.icon',
            'exclude' => true,
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title,icon,config,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
];
