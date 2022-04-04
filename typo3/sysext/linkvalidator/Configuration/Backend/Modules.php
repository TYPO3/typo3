<?php

use TYPO3\CMS\Linkvalidator\Controller\LinkValidatorController;

/**
 * Definitions for modules provided by EXT:linkvalidator
 */
return [
    'web_linkvalidator' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/link-reports',
        'iconIdentifier' => 'module-linkvalidator',
        'labels' => 'LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang_mod.xlf',
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
