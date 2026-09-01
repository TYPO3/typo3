<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Startingpoint Test Dummy',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:test_startingpoint/Resources/Public/Icons/icon_tx_startingpoint_domain_model_dummy.gif',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, hidden, title'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
