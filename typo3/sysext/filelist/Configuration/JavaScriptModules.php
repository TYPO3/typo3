<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.contextmenu',
    ],
    'imports' => [
        '@typo3/filelist/' => 'EXT:filelist/Resources/Public/JavaScript/',
    ],
];
