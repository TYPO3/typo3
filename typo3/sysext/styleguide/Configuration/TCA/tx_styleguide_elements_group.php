<?php
return [
    'ctrl' => [
        'title' => 'Form engine elements - group',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],


    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0'
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],


        'group_1' => [
            'exclude' => 1,
            'label' => 'group_1 db, allowed=be_users,be_groups',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'maxitems' => 999,
            ],
        ],
        'group_2' => [
            'exclude' => 1,
            'label' => 'group_2 db, allowed=be_users,be_groups, show_thumbs=true',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'show_thumbs' => true,
                'maxitems' => 999,
            ],
        ],
        'group_3' => [
            'exclude' => 1,
            'label' => 'group_3 db, allowed=tx_styleguide_forms_staticdata, wizard suggest, disable_controls=browser',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'disable_controls' => 'browser',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest',
                    ],
                ],
                'maxitems' => 999,
            ],
        ],
        'group_4' => [
            'exclude' => 1,
            'label' => 'group_4 db, allowed=tx_styleguide_forms_staticdata, show_thumbs=true, size=1, wizard suggest',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'show_thumbs' => true,
                'size' => 1,
                'maxitems' => 1,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest',
                    ],
                ],
            ],
        ],
        'group_5' => [
            'exclude' => 1,
            'label' => 'group_5 db, readOnly=1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users',
                'readOnly' => 1,
            ]
        ],
        'group_6' => [
            // @todo: when and why is this useful?
            'exclude' => 1,
            'label' => 'group_6 db, FAL relation',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_file',
                'MM' => 'sys_file_reference',
                'MM_match_fields' => [
                    'fieldname' => 'image_fal_group',
                ],
                'prepend_tname' => true,
                'appearance' => [
                    'elementBrowserAllowed' => 'jpg, png, gif',
                    'elementBrowserType' => 'file',
                ],
                'max_size' => 2000,
                'show_thumbs' => true,
                'size' => '3',
                'maxitems' => 200,
                'autoSizeMax' => 40,
            ],
        ],

        'group_7' => [
            'exclude' => 1,
            'label' => 'group_7 file, show_thumbs=true',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg, jpeg, png, gif',
                'disallowed' => 'ai',
                'show_thumbs' => true,
                'size' => 3,
                'uploadfolder' => 'uploads/pics/',
                // @todo: Documented feature has no effect since upload field in form is not shown anymore (since fal?)
                'disable_controls' => 'upload',
                'max_size' => 2000,
                // @todo: does maxitems = 1 default hit here? YES!
                'maxitems' => 999,
            ],
        ],
        'group_8' => [
            'exclude' => 1,
            'label' => 'group_8 file, disable_controls=delete',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'size' => 3,
                'uploadfolder' => 'uploads/pics/',
                'disable_controls' => 'delete',
            ],
        ],
        'group_9' => [
            'exclude' => 1,
            'label' => 'group_9 file, size=1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'size' => 1,
                'uploadfolder' => 'uploads/pics/',
            ],
        ],
        'group_10' => [
            'exclude' => 1,
            'label' => 'group_10 file, selectedListStyles used',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'uploadfolder' => 'uploads/pics/',
                'selectedListStyle' => 'width:400px;background-color:#ccffcc;',
            ],
        ],

        'group_11' => [
            'exclude' => 1,
            'label' => 'group_11 folder',
            'config' => [
                'type' => 'group',
                'internal_type' => 'folder',
                'maxitems' => 999,
            ],
        ],

    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;internal_type=db,
                    group_1, group_2, group_3, group_4, group_5, group_6,
                --div--;internal_type=file,
                    group_7, group_8, group_9, group_10,
                --div--;internal_type=folder,
                    group_11,
            ',
        ],
    ],


];
