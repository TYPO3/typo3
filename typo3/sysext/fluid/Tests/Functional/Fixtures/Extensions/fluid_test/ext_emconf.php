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
    'clearCacheOnLoad' => 0,
    'version' => '11.5.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'TYPO3Fluid\\FluidTest\\' => 'Classes',
        ],
    ],
    'autoload-dev' => [
        'psr-4' => [
            'TYPO3Fluid\\FluidTest\\Tests\\' => 'Tests',
        ],
    ],
];
