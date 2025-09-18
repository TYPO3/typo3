<?php

return [
    'dependencies' => [],
    'imports' => [
        '@typo3/core/' => [
            'path' => 'EXT:test_importmap_core/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:core/Resources/Public/JavaScript/Contrib/',
            ],
        ],
        'lit' => 'EXT:test_importmap_core/Resources/Public/JavaScript/Contrib/lit/index.js',
        'lit/' => 'EXT:test_importmap_core/Resources/Public/JavaScript/Contrib/lit/',
    ],
];
