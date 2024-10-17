<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Form',
    'description' => 'Flexible TYPO3 frontend form framework that comes with a backend editor interface.',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '14.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0',
            'frontend' => '14.0.0',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '14.0.0',
            'impexp' => '14.0.0',
            'lowlevel' => '14.0.0',
        ],
    ],
];
