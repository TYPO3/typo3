<?php

return [
    'ctrl' => [
        'label' => 'uid',
        'title' => 'A',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
    ],
    'columns' => [
        'a' => [
            'label' => 'a',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'max' => 255,
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,a']
    ],
];
