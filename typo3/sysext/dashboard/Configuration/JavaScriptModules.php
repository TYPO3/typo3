<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'imports' => [
        '@typo3/dashboard/' => [
            'path' => 'EXT:dashboard/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:dashboard/Resources/Public/JavaScript/Contrib/',
            ],
        ],
        'chart.js' => 'EXT:dashboard/Resources/Public/JavaScript/Contrib/chartjs.js',
        // legacy, has been renamed "chart.js"
        '@typo3/dashboard/contrib/chartjs.js' => 'EXT:dashboard/Resources/Public/JavaScript/Contrib/chartjs.js',
    ],
];
