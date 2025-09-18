<?php

return [
    'dependencies' => [
        'test_importmap_core',
    ],
    'imports' => [
        '@typo3/package2/' => 'EXT:test_importmap_package2/Resources/Public/JavaScript/',
    ],
];
