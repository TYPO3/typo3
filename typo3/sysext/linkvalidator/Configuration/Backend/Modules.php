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
        'icon' => 'EXT:linkvalidator/Resources/Public/Icons/Extension.png',
        // @todo Uncomment following line after updating TYPO3/TYPO3.Icons
        // 'iconIdentifier' => 'module-linkvalidator'
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
