<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Extension skeleton for TYPO3 7',
    'description' => 'Description for ext',
    'category' => 'Example Extensions',
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => 'helhum.io',
    'state' => 'stable',
    'version' => '13.4.14',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.14',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'TYPO3Tests\\FluidTest\\' => 'Classes',
        ],
    ],
];
