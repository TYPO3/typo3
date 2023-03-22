<?php

use TYPO3\CMS\Viewpage\Controller\ViewModuleController;

/**
 * Definitions for modules provided by EXT:viewpage
 */
return [
    'page_preview' => [
        'parent' => 'web',
        'position' => ['after' => 'web_layout'],
        'access' => 'user',
        'path' => '/module/web/viewpage',
        'iconIdentifier' => 'module-viewpage',
        'labels' => 'LLL:EXT:viewpage/Resources/Private/Language/locallang_mod.xlf',
        'aliases' => ['web_ViewpageView'],
        'routes' => [
            '_default' => [
                'target' => ViewModuleController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'language' => 0,
        ],
    ],
];
