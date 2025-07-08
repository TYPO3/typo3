<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS System Notes',
    'description' => 'Records with messages which can be placed on any page and contain instructions or other information related to a page or section.',
    'category' => 'be',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.4.16',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.16',
        ],
        'conflicts' => [],
        'suggests' => [
            'dashboard' => '',
        ],
    ],
];
