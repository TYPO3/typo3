<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.module',
    ],
    'imports' => [
        '@typo3/tstemplate/' => 'EXT:tstemplate/Resources/Public/JavaScript/',
    ],
];
