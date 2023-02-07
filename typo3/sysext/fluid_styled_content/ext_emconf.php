<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Styled Content',
    'description' => 'Fluid templates for TYPO3 content elements.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '10.4.35',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.35',
            'fluid' => '10.4.35',
            'frontend' => '10.4.35',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
