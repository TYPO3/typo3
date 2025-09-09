<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid ViewHelperResolver delegate tests',
    'description' => '',
    'category' => 'Example Extensions',
    'state' => 'stable',
    'version' => '13.4.19',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.19',
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
