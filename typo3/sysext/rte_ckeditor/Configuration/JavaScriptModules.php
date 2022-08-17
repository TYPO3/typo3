<?php

return [
    'dependencies' => [
        'backend',
    ],
    'imports' => [
        '@typo3/rte-ckeditor/' => [
            'path' => 'EXT:rte_ckeditor/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:rte_ckeditor/Resources/Public/JavaScript/Contrib/',
                'EXT:rte_ckeditor/Resources/Public/JavaScript/Plugins/',
            ],
        ],
        '@typo3/ckeditor5-bundle.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/ckeditor5-bundle.js',
    ],
];
