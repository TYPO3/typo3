<?php

return [
    'dependencies' => [
        'backend',
    ],
    'imports' => [
        'TYPO3/CMS/T3editor/' => [
            'path' => 'EXT:t3editor/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:t3editor/Resources/Public/JavaScript/Addon/',
                'EXT:t3editor/Resources/Public/JavaScript/Contrib/',
                'EXT:t3editor/Resources/Public/JavaScript/Mode/',
            ],
        ],
    ],
];
