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
    ],
    'columns' => [
        // The owner of the dashboard
        'cruser_id' => [
            'config' => [
                'type' => 'passthrough',
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
                --div--;core.form.tabs:general,
                    identifier,title,
                --div--;core.form.tabs:access,
                    hidden, --palette--;;timeRestriction,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
    ],
];
