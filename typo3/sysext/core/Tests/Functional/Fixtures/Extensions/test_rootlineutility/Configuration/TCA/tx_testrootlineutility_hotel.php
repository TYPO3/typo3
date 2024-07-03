<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Hotels',
        'label' => 'title',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:test_rootlineutility/Resources/Public/Icons/icon_hotel.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'label' => 'Hotel title',
            'config' => [
                'type' => 'text',
                'rows' => 1,
                'required' => true,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;General, title,' .
            '--div--;Visibility, sys_language_uid, l10n_parent, hidden, starttime, endtime',
        ],
    ],
];
