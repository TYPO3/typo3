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
        'config' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.config',
            'config' => [
                'type' => 'text',
                'renderType' => 'belayoutwizard',
            ],
        ],
        'icon' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.icon',
            'exclude' => true,
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'relationship' => 'manyToOne',
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
