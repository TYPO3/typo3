<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Integration',
    'description' => 'Integration of the Fluid templating engine into TYPO3.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '13.0.1',
    'constraints' => [
        'depends' => [
            'core' => '13.0.1',
            'extbase' => '13.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
