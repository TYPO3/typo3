<?php

return [
    'dependencies' => [
        'backend',
        'recordlist',
    ],
    'imports' => [
        '@typo3/rte-ckeditor/' => [
            'path' => 'EXT:rte_ckeditor/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:rte_ckeditor/Resources/Public/JavaScript/Contrib/',
                'EXT:rte_ckeditor/Resources/Public/JavaScript/Plugins/',
            ],
        ],
    ],
];
