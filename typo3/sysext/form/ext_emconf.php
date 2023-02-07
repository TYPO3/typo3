<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Form',
    'description' => 'Flexible TYPO3 frontend form framework that comes with a backend editor interface.',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '10.4.35',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.35',
        ],
        'conflicts' => [],
        'suggests' => [
            'filelist' => '10.4.35',
            'impexp' => '10.4.35',
        ],
    ],
];
