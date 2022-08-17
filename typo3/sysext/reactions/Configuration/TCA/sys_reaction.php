<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction',
        'label' => 'name',
        'descriptionColumn' => 'description',
        'crdate' => 'createdon',
        'tstamp' => 'updatedon',
        'adminOnly' => true,
        'rootLevel' => 1,
        'groupName' => 'system',
        'default_sortby' => 'name',
        'type' => 'reactiontype',
        'typeicon_column' => 'reactiontype',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_reaction',
        ],
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'name,secret',
        'versioningWS_alwaysAllowLiveEdit' => true,
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;config,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;access',
        ],
    ],
    'palettes' => [
        'config' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:palette.config',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:palette.config.description',
            'showitem' => 'reactiontype, --linebreak--, name, description, --linebreak--, identifier, secret',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'disabled, starttime, endtime',
        ],
    ],
    'columns' => [
        'reactiontype' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.reactiontype',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.reactiontype.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    ['LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.reactiontype.select', ''],
                ],
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.name',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.name.description',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.description',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.description.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
            ],
        ],
        'identifier' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.identifier',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.identifier.description',
            'config' => [
                'type' => 'user',
                'renderType' => 'uuid',
            ],
        ],
        'secret' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.secret',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.secret.description',
            'config' => [
                'type' => 'password',
                'required' => true,
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.secret.passwordGenerator',
                            'allowEdit' => false,
                            'passwordRules' => [
                                'length' => 40,
                                'random' => 'hex',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'impersonate_user' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.impersonate_user',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.impersonate_user.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'disabled' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
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
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
        ],
    ],
];
