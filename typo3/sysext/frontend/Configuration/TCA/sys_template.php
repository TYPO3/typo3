<?php

return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template',
        'groupName' => 'system',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        // Only admin, if any
        'adminOnly' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_column' => 'root',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-template-extension',
            '1' => 'mimetypes-x-content-template',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.title',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'max' => 255,
                'required' => true,
            ],
        ],
        'root' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.root',
            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.rootDescription',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'clear' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.clear',
            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.clearDescription',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'Constants'],
                    ['label' => 'Setup'],
                ],
            ],
        ],
        'constants' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.constants',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 15,
                'wrap' => 'off',
                'enableTabulator' => true,
                'fixedFont' => true,
                'softref' => 'email[subst],url[subst]',
            ],
        ],
        'include_static_file' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.include_static_file',
            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.include_static_fileDescription',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => [],
                'softref' => 'ext_fileref',
            ],
        ],
        'basedOn' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.basedOn',
            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.basedOnDescription',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_template',
                'maxitems' => 50,
                'autoSizeMax' => 10,
                'default' => '',
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.basedOn_edit',
                        ],
                    ],
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.basedOn_add',
                            'setValue' => 'prepend',
                        ],
                    ],
                ],
            ],
        ],
        'includeStaticAfterBasedOn' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.includeStaticAfterBasedOn',
            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.includeStaticAfterBasedOnDescription',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'config' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.config',
            'config' => [
                'type' => 'text',
                'rows' => 15,
                'cols' => 48,
                'wrap' => 'off',
                'enableTabulator' => true,
                'fixedFont' => true,
                'softref' => 'email[subst],url[subst]',
            ],
        ],
        'static_file_mode' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode',
            'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_modeDescription',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.0', 'value' => '0'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.1', 'value' => '1'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.2', 'value' => '2'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.3', 'value' => '3'],
                ],
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;core.form.tabs:general,
                title, constants, config,
            --div--;core.form.tabs:advancedoptions,
                clear, root, include_static_file, basedOn, includeStaticAfterBasedOn, static_file_mode,
            --div--;core.form.tabs:access,
                hidden,--palette--;;timeRestriction,
            --div--;core.form.tabs:notes,
                description,
            --div--;core.form.tabs:extended,
        '],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
    ],
];
