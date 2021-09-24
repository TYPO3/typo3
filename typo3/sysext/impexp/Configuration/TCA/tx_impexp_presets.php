<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_tca.xlf:tx_impexp_presets',
        'label' => 'title',
        'default_sortby' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'user_uid',
        'typeicon_classes' => [
            'default' => 'actions-cog',
        ],
        'hideTable' => true,
        'rootLevel' => -1,
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_tca.xlf:title',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'public' => [
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_tca.xlf:public',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'user_uid' => [
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_tca.xlf:user_uid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'item_uid' => [
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_tca.xlf:item_uid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'preset_data' => [
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_tca.xlf:preset_data',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        0 => [
            'showitem' => 'title, public, user_uid, item_uid, preset_data',
        ],
    ],
];
