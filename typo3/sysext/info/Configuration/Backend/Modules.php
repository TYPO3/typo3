<?php

use TYPO3\CMS\Info\Controller\InfoModuleController;

/**
 * Definitions for modules provided by EXT:info
 */
return [
    'web_info' => [
        'parent' => 'web',
        'access' => 'user',
        'path' => '/module/web/info',
        'iconIdentifier' => 'module-info',
        'labels' => 'LLL:EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf',
        'routes' => [
            '_default' => [
                'target' => InfoModuleController::class . '::mainAction',
            ],
        ],
    ],
];
