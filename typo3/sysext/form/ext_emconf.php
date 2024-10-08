<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Form',
    'description' => 'Flexible TYPO3 frontend form framework that comes with a backend editor interface.',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.3.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.3.2',
            'frontend' => '13.3.2',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '13.3.2',
            'impexp' => '13.3.2',
            'lowlevel' => '13.3.2',
        ],
    ],
];
