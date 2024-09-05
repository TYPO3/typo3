<?php

return [
    'dashboardPreset-SysNotes' => [
        'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget_group.sys_note_widget.title',
        'description' => 'Shows all widgets from EXT:sys_note',
        'iconIdentifier' => 'content-note',
        'defaultWidgets' => ['sys_note_all', 'sys_note_default', 'sys_note_instructions', 'sys_note_template', 'sys_note_notes', 'sys_note_todos'],
        'showInWizard' => true,
    ],
];
