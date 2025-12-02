<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Backend User',
    'description' => 'TYPO3 backend module "Administration > Users" for managing backend users and groups.',
    'category' => 'module',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'state' => 'stable',
    'version' => '14.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
