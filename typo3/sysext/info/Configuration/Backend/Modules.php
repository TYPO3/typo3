<?php

use TYPO3\CMS\Info\Controller\PageInformationController;
use TYPO3\CMS\Info\Controller\TranslationStatusController;

/**
 * Definitions for modules provided by EXT:info
 */
return [
    'web_info_overview' => [
        'parent' => 'content_status',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/web/info/overview',
        'iconIdentifier' => 'module-info',
        'labels' => 'info.modules.overview',
        'routes' => [
            '_default' => [
                'target' => PageInformationController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'pages' => '0',
            'depth' => 0,
            'lang' => 0,
        ],
    ],
    'web_info_translations' => [
        'parent' => 'content_status',
        'position' => ['after' => 'web_info_overview'],
        'access' => 'user',
        'path' => '/module/web/info/translations',
        'iconIdentifier' => 'module-info',
        'labels' => 'info.modules.translations',
        'routes' => [
            '_default' => [
                'target' => TranslationStatusController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'depth' => 0,
            'lang' => 0,
        ],
    ],
];
