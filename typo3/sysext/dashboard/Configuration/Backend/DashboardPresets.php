<?php
return [
    'default' => [
        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default',
        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default.description',
        'iconIdentifier' => 'dashboard-default',
        'defaultWidgets' => ['t3information', 't3news', 'docGettingStarted'],
        'showInWizard' => false
    ],
    'empty' => [
        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.empty',
        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.empty.description',
        'iconIdentifier' => 'dashboard-empty',
        'defaultWidgets' => [],
        'showInWizard' => true
    ],
];
