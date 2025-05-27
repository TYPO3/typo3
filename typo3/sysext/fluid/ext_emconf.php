<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Integration',
    'description' => 'Integration of the Fluid templating engine into TYPO3.',
    'category' => 'fe',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '12.4.33',
    'constraints' => [
        'depends' => [
            'core' => '12.4.33',
            'extbase' => '12.4.33',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
