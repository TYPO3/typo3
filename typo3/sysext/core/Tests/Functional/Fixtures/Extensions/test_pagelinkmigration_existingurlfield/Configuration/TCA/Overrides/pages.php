<?php

defined('TYPO3') || die('Access restricted');

$GLOBALS['TCA']['pages']['columns']['url'] = [
    'label' => 'core.db.pages:url',
    'config' => [
        'type' => 'input',
        'size' => 50,
        'max' => 255,
        'required' => true,
        'eval' => 'trim',
        'softref' => 'url',
        'behaviour' => [
            'allowLanguageSynchronization' => true,
        ],
    ],
];
