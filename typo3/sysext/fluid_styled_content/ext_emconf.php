<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid Styled Content',
    'description' => 'A set of common content elements based on Fluid for Frontend output.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'version' => '9.5.3',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.3',
            'fluid' => '9.5.3',
            'frontend' => '9.5.3',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
