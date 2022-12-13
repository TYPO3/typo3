<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Styled Content',
    'description' => 'Fluid templates for TYPO3 content elements.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '12.1.3',
    'constraints' => [
        'depends' => [
            'typo3' => '12.1.3',
            'fluid' => '12.1.3',
            'frontend' => '12.1.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
