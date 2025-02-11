<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Install Tool',
    'description' => 'The Install Tool is used for installation, upgrade, system administration and setup tasks.',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '13.4.6',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.6',
            'extbase' => '13.4.6',
            'fluid' => '13.4.6',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
