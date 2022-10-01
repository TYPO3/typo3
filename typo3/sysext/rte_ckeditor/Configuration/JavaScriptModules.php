<?php

return [
    'dependencies' => [
        'backend',
    ],
    'imports' => [
        '@typo3/rte-ckeditor/' => 'EXT:rte_ckeditor/Resources/Public/JavaScript/',
        '@typo3/ckeditor5-bundle.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/ckeditor5-bundle.js',
    ],
];
