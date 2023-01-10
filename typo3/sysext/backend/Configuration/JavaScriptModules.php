<?php

return [
    'dependencies' => [
        'core',
    ],
    'tags' => [
        'backend.module',
        'backend.form',
        'backend.navigation-component',
    ],
    'imports' => [
        '@typo3/backend/' => 'EXT:backend/Resources/Public/JavaScript/',
    ],
];
