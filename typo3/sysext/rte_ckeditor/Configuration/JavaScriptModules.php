<?php

return [
    'dependencies' => [
        'backend',
    ],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@typo3/rte-ckeditor/' => 'EXT:rte_ckeditor/Resources/Public/JavaScript/',
        '@typo3/ckeditor5-bundle.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/ckeditor5-bundle.js',
        '@typo3/ckeditor5-inspector.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/ckeditor5-inspector.js',
        '@typo3/ckeditor5/translations/' => 'EXT:rte_ckeditor/Resources/Public/Contrib/translations/',
    ],
];
