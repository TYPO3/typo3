<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Styled Content',
    'description' => 'Fluid templates for TYPO3 content elements.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '12.4.37',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.37',
            'fluid' => '12.4.37',
            'frontend' => '12.4.37',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
