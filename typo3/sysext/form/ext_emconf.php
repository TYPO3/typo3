<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Form',
    'description' => 'Flexible TYPO3 frontend form framework that comes with a backend editor interface.',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.4.37',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.37',
            'frontend' => '13.4.37',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '13.4.37',
            'impexp' => '13.4.37',
            'lowlevel' => '13.4.37',
        ],
    ],
];
