<?php

return [
    'dependencies' => [
        'test_importmap_core',
        'test_importmap_package2',
    ],
    'imports' => [
        '@typo3/package3/' => [
            'path' => 'EXT:test_importmap_package3/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:test_importmap_package3/Resources/Public/JavaScript/Contrib/',
                'EXT:test_importmap_package3/Resources/Public/JavaScript/Overrides/',
            ],
        ],
        'lib1' => 'EXT:test_importmap_package3/Resources/Public/JavaScript/Contrib/lib1.js',
        'lib2' => 'EXT:test_importmap_package3/Resources/Public/JavaScript/Contrib/lib2.js',
        // overriding a file from EXT:package2
        '@typo3/package2/File.js' => 'EXT:test_importmap_package3/Resources/Public/JavaScript/Overrides/Package2/File.js',
    ],
];
