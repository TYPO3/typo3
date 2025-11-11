<?php

use TYPO3\CMS\Viewpage\Controller\ViewModuleController;

/**
 * Definitions for modules provided by EXT:viewpage
 */
return [
    'page_preview' => [
        'parent' => 'content',
        'position' => ['after' => 'web_list'],
        'access' => 'user',
        'path' => '/module/page-preview',
        'iconIdentifier' => 'module-viewpage',
        'labels' => 'viewpage.module',
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
