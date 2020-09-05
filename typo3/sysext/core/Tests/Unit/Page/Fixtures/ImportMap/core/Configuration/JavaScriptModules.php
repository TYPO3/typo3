<?php

return [
    'dependencies' => [],
    'imports' => [
        '@typo3/core/' => [
            'path' => 'EXT:core/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:core/Resources/Public/JavaScript/Contrib/',
            ],
        ],
        'lit' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit/index.js',
        'lit/' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit/',
    ],
];
