<?php

return [
    'ctrl' => [
        'title' => 'impexp.db:tx_impexp_presets',
        'label' => 'title',
        'default_sortby' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'typeicon_classes' => [
            'default' => 'actions-cog',
        ],
        'hideTable' => true,
        'rootLevel' => -1,
    ],
    'columns' => [
        'title' => [
            'label' => 'impexp.db:title',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'public' => [
            'label' => 'impexp.db:public',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'user_uid' => [
            'label' => 'impexp.db:user_uid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'item_uid' => [
            'label' => 'impexp.db:item_uid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'preset_data' => [
            'label' => 'impexp.db:preset_data',
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
