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
        '@typo3/install/' => 'EXT:install/Resources/Public/JavaScript/',
    ],
];
