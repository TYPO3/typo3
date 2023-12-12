<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Extbase',
    'description' => 'Extension framework to create TYPO3 frontend plugins and TYPO3 backend modules.',
    'category' => 'misc',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '12.4.10',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.10',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '',
        ],
    ],
];
