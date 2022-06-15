<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid Styled Content',
    'description' => 'A set of common content elements based on Fluid for Frontend output.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '11.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.13',
            'fluid' => '11.5.13',
            'frontend' => '11.5.13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
