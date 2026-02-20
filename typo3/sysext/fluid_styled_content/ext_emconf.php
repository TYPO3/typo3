<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Styled Content',
    'description' => 'Fluid templates for TYPO3 content elements.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '14.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '14.1.2',
            'fluid' => '14.1.2',
            'frontend' => '14.1.2',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
