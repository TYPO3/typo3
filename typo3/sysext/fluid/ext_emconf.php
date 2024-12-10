<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Integration',
    'description' => 'Integration of the Fluid templating engine into TYPO3.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.4.3',
    'constraints' => [
        'depends' => [
            'core' => '13.4.3',
            'extbase' => '13.4.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
