<?php

return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'default_sortby' => 'title',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_filemounts',
        'adminOnly' => true,
        'rootLevel' => 1,
        'groupName' => 'backendaccess',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_filemounts',
        ],
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'title,path',
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_filemounts.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => 2000,
            ],
        ],
        'identifier' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_filemounts.identifier',
            'config' => [
                'type' => 'folder',
                'required' => true,
                'maxitems' => 1,
                'size' => 1,
            ],
        ],
        'read_only' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_filemounts.read_only',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                title, identifier, read_only,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ],
];
