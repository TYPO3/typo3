<?php

return [
    'ctrl' => [
        'label' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'delete' => 'deleted',
        'groupName' => 'content',
        'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_note',
        ],
        'sortby' => 'sorting',
        'searchFields' => 'subject,message',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'category' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => '0', 'icon' => 'sysnote-type-0'],
                    ['label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.1', 'value' => '1', 'icon' => 'sysnote-type-1'],
                    ['label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.3', 'value' => '3', 'icon' => 'sysnote-type-3'],
                    ['label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.4', 'value' => '4', 'icon' => 'sysnote-type-4'],
                    ['label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.2', 'value' => '2', 'icon' => 'sysnote-type-2'],
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'subject' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.subject',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
            ],
        ],
        'message' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.message',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
            ],
        ],
        'personal' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.personal',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'position' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.position',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.position.top',
                        'value' => \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::SYS_NOTE_POSITION_TOP,
                    ],
                    [
                        'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.position.bottom',
                        'value' => \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::SYS_NOTE_POSITION_BOTTOM,
                    ],
                ],
                'default' => \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::SYS_NOTE_POSITION_BOTTOM,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        // The owner of the note
        'cruser' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                category, subject,message,position,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                personal,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ],
];
