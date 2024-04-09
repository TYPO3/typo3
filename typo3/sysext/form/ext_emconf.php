<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Form',
    'description' => 'Flexible TYPO3 frontend form framework that comes with a backend editor interface.',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '12.4.15',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.15',
            'frontend' => '12.4.15',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '12.4.15',
            'impexp' => '12.4.15',
            'lowlevel' => '12.4.15',
        ],
    ],
];
