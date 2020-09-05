<?php

return [
    'dependencies' => [
        'core',
        'package2',
    ],
    'imports' => [
        'TYPO3/CMS/Package3/' => [
            'path' => 'EXT:package3/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:package3/Resources/Public/JavaScript/Contrib/',
                'EXT:package3/Resources/Public/JavaScript/Overrides/',
            ],
        ],
        'lib1' => 'EXT:package3/Resources/Public/JavaScript/Contrib/lib1.js',
        'lib2' => 'EXT:package3/Resources/Public/JavaScript/Contrib/lib2.js',
        // overriding a file from EXT:package2
        'TYPO3/CMS/Package2/File.js' => 'EXT:package3/Resources/Public/JavaScript/Overrides/Package2/File.js',
    ],
];
