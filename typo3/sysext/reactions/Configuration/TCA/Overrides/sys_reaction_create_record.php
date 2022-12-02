<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'sys_reaction',
    [
        'table_name' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.table_name',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.table_name.description',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'default' => '',
                'items' => [
                    ['LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.table_name.select', ''],
                ],
                'itemsProcFunc' => \TYPO3\CMS\Reactions\Form\ReactionItemsProcFunc::class . '->populateAvailableContentTables',
            ],
        ],
        'storage_pid' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.storage_pid',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.storage_pid.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'fields' => [
            'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.fields',
            'description' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.fields.description',
            'displayCond' => 'FIELD:table_name:REQ:true',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldMap',
                'dbType' => 'json',
                'default' => '{}',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'sys_reaction',
    'reaction_type',
    [
        \TYPO3\CMS\Reactions\Reaction\CreateRecordReaction::getDescription(),
        \TYPO3\CMS\Reactions\Reaction\CreateRecordReaction::getType(),
        \TYPO3\CMS\Reactions\Reaction\CreateRecordReaction::getIconIdentifier(),
    ]
);

$GLOBALS['TCA']['sys_reaction']['ctrl']['typeicon_classes'][\TYPO3\CMS\Reactions\Reaction\CreateRecordReaction::getType()] = \TYPO3\CMS\Reactions\Reaction\CreateRecordReaction::getIconIdentifier();

$GLOBALS['TCA']['sys_reaction']['palettes']['createRecord'] = [
    'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:palette.additional',
    'showitem' => 'table_name, --linebreak--, storage_pid, impersonate_user, --linebreak--, fields',
];

$GLOBALS['TCA']['sys_reaction']['types'][\TYPO3\CMS\Reactions\Reaction\CreateRecordReaction::getType()] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        --palette--;;config,
        --palette--;;createRecord,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;access',
    'columnsOverrides' => [
        'impersonate_user' => [
            'config' => [
                'minitems' => 1,
            ],
        ],
    ],
];
