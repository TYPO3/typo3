<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'A Blog Example for the Extbase Framework',
    'description' => 'An example extension demonstrating the features of the Extbase Framework. It is the back-ported and tweaked Blog Example package of TYPO3.Flow. Have fun playing with it!',
    'category' => 'example',
    'author' => 'TYPO3 core team',
    'author_company' => '',
    'author_email' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '11.5.11',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.11',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
