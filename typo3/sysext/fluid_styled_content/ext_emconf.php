<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Fluid Styled Content',
    'description' => 'Fluid templates for TYPO3 content elements.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '13.4.3',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.3',
            'fluid' => '13.4.3',
            'frontend' => '13.4.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
