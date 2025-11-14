<?php

use TYPO3\CMS\Linkvalidator\Controller\LinkValidatorController;

/**
 * Definitions for modules provided by EXT:linkvalidator
 */
return [
    'web_linkvalidator' => [
        'parent' => 'content_status',
        'position' => ['after' => 'web_info_translations'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/link-reports',
        'iconIdentifier' => 'module-linkvalidator',
        'labels' => 'linkvalidator.module',
        'routes' => [
            '_default' => [
                'target' => LinkValidatorController::class,
            ],
        ],
        'moduleData' => [
            'action' => 'report',
        ],
    ],
];
