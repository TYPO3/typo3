<?php

return [
    'dependencies' => [
        'backend',
        'core',
        't3editor',
    ],
    'tags' => [
        'backend.module',
    ],
    'imports' => [
        '@typo3/tstemplate/' => 'EXT:tstemplate/Resources/Public/JavaScript/',
    ],
];
