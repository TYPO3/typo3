<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid ViewHelperResolver delegate tests',
    'description' => '',
    'category' => 'Example Extensions',
    'state' => 'stable',
    'version' => '14.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'TYPO3Tests\\ResolverdelegateTest\\' => 'Classes',
        ],
    ],
];
