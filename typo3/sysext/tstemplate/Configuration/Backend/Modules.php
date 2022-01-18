<?php

use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;

/**
 * Definitions for modules provided by EXT:tstemplate
 */
return [
    'web_ts' => [
        'parent' => 'web',
        'access' => 'admin',
        'path' => '/module/web/ts',
        'iconIdentifier' => 'module-tstemplate',
        'labels' => 'LLL:EXT:tstemplate/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => TypoScriptTemplateModuleController::class . '::mainAction',
            ],
        ],
    ],
];
