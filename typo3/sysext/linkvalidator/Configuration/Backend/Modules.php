<?php

use TYPO3\CMS\Linkvalidator\Controller\LinkValidatorController;

/**
 * Definitions for modules provided by EXT:linkvalidator
 */
return [
    'linkvalidator_checklinks' => [
        'parent' => 'link_management',
        'position' => ['after' => 'short_urls'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/link-management/check-links',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'iconIdentifier' => 'module-linkvalidator',
        'labels' => 'linkvalidator.module',
        'routes' => [
            '_default' => [
                'target' => LinkValidatorController::class,
            ],
        ],
        'aliases' => ['web_linkvalidator'],
        'moduleData' => [
            'action' => 'report',
        ],
    ],
];
