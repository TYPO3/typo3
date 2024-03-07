<?php

return [
    'dependencies' => ['backend'],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@my-vendor/my-package/timestamp-plugin.js' => 'EXT:my_extension/Resources/Public/JavaScript/Ckeditor/timestamp-plugin.js',
    ],
];
