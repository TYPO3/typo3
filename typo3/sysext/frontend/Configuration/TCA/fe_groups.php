<?php

return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title',
        'groupName' => 'frontendaccess',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups',
        'typeicon_classes' => [
            'default' => 'status-user-group-frontend',
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 50,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'subgroup' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.subgroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'AND NOT({#fe_groups}.{#uid} = ###THIS_UID###)',
                'size' => 6,
                'autoSizeMax' => 10,
                'maxitems' => 20,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;core.form.tabs:general,
                title,subgroup,
            --div--;core.form.tabs:access,
                hidden,
            --div--;core.form.tabs:notes,
                description,
            --div--;core.form.tabs:extended,
        '],
    ],
];
