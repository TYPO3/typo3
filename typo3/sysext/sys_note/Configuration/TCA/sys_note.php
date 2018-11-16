<?php
return [
    'ctrl' => [
        'label' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'delete' => 'deleted',
        'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_note'
        ],
        'sortby' => 'sorting',
        'searchFields' => 'subject,message'
    ],
    'interface' => [
        'showRecordFieldList' => 'category,subject,message,personal'
    ],
    'columns' => [
        'category' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', '0', 'sysnote-type-0'],
                    ['LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.1', '1', 'sysnote-type-1'],
                    ['LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.3', '3', 'sysnote-type-3'],
                    ['LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.4', '4', 'sysnote-type-4'],
                    ['LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.2', '2', 'sysnote-type-2']
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ]
        ],
        'subject' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.subject',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255
            ]
        ],
        'message' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.message',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15
            ]
        ],
        'personal' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.personal',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ]
                ],
            ]
        ],
        'position' => [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.position',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.position.top',
                        \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::SYS_NOTE_POSITION_TOP
                    ],
                    [
                        'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.position.bottom',
                        \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::SYS_NOTE_POSITION_BOTTOM
                    ],
                ],
                'default' => \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository::SYS_NOTE_POSITION_BOTTOM,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ]
        ]
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                category, subject,message,position,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                personal,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ']
    ]
];
