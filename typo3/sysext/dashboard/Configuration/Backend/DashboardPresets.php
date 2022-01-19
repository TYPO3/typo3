<?php

return [
    'default' => [
        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default',
        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default.description',
        'iconIdentifier' => 'content-dashboard',
        'defaultWidgets' => ['t3information', 'docGettingStarted'],
        'showInWizard' => false,
    ],
    'empty' => [
        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.empty',
        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.empty.description',
        'iconIdentifier' => 'content-dashboard-empty',
        'defaultWidgets' => [],
        'showInWizard' => true,
    ],
];
