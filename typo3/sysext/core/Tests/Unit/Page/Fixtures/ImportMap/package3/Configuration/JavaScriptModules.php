<?php

return [
    'dependencies' => [
        'core',
        'package2',
    ],
    'imports' => [
        '@typo3/package3/' => [
            'path' => 'EXT:package3/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:package3/Resources/Public/JavaScript/Contrib/',
                'EXT:package3/Resources/Public/JavaScript/Overrides/',
            ],
        ],
        'lib1' => 'EXT:package3/Resources/Public/JavaScript/Contrib/lib1.js',
        'lib2' => 'EXT:package3/Resources/Public/JavaScript/Contrib/lib2.js',
        // overriding a file from EXT:package2
        '@typo3/package2/File.js' => 'EXT:package3/Resources/Public/JavaScript/Overrides/Package2/File.js',
    ],
];
