<?php

use TYPO3\CMS\Redirects\Controller\ManagementController;
use TYPO3\CMS\Redirects\Controller\QrCodeModuleController;
use TYPO3\CMS\Redirects\Controller\ShortUrlModuleController;

/**
 * Definitions for modules provided by EXT:redirects
 */
return [
    'redirects' => [
        'parent' => 'link_management',
        'access' => 'user',
        'path' => '/module/link-management/redirects',
        'iconIdentifier' => 'module-redirects',
        'labels' => 'redirects.modules.redirects',
        'aliases' => ['site_redirects'],
        'routes' => [
            '_default' => [
                'target' => ManagementController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'redirectType' => 'default',
        ],
    ],
    'qrcodes' => [
        'parent' => 'link_management',
        'access' => 'user',
        'path' => '/module/link-management/qrcodes',
        'iconIdentifier' => 'module-qrcode',
        'labels' => 'redirects.modules.qrcodes',
        'routes' => [
            '_default' => [
                'target' => QrCodeModuleController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'redirectType' => 'qrcode',
        ],
    ],
    'short_urls' => [
        'parent' => 'link_management',
        'access' => 'user',
        'path' => '/module/link-management/short-urls',
        'iconIdentifier' => 'module-urls',
        'labels' => 'redirects.modules.short_urls',
        'routes' => [
            '_default' => [
                'target' => ShortUrlModuleController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'redirectType' => 'short_url',
        ],
    ],
];
