<?php

return [
    'dependencies' => [
        'backend',
        'recordlist',
    ],
    'imports' => [
        'TYPO3/CMS/RteCkeditor/' => [
            'path' => 'EXT:rte_ckeditor/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:rte_ckeditor/Resources/Public/JavaScript/Contrib/',
                'EXT:rte_ckeditor/Resources/Public/JavaScript/Plugins/',
            ],
        ],
    ],
];
