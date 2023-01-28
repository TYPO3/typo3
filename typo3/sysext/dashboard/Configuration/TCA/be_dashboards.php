<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_tca.xlf:be_dashboard',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'adminOnly' => true,
        'rootLevel' => 1,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'default_sortby' => 'crdate DESC',
        'typeicon_classes' => [
            'default' => 'content-dashboard',
        ],
        'searchFields' => 'identifier,title',
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
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
        // The owner of the dashboard
        'cruser_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'starttime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'identifier' => [
            'label' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_tca.xlf:identifier',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang_tca.xlf:title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    identifier,title,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden, --palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
    ],
];
