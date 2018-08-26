<?php

defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules'] = [
    'preview' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\PreviewModule::class,
        'before' => ['cache'],
    ],
    'cache' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\CacheModule::class,
        'after' => ['preview'],
    ],
    'edit' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\EditModule::class,
        'after' => ['cache'],
    ],
    'tsdebug' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\TsDebugModule::class,
        'after' => ['edit'],
        'submodules' => [
            'ts-waterfall' => [
                'module' => \TYPO3\CMS\Adminpanel\Modules\TsDebug\TypoScriptWaterfall::class,
            ],
        ],
    ],
    'info' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\InfoModule::class,
        'after' => ['tsdebug'],
        'submodules' => [
            'general' => [
                'module' => \TYPO3\CMS\Adminpanel\Modules\Info\GeneralInformation::class,
            ],
            'request' => [
                'module' => \TYPO3\CMS\Adminpanel\Modules\Info\RequestInformation::class,
            ],
            'phpinfo' => [
                'module' => \TYPO3\CMS\Adminpanel\Modules\Info\PhpInformation::class,
            ],
        ],
    ],
    'debug' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\DebugModule::class,
        'after' => ['info'],
        'submodules' => [
            'log' => [
                'module' => \TYPO3\CMS\Adminpanel\Modules\Debug\Log::class,
            ],
            'queryInformation' => [
                'module' => \TYPO3\CMS\Adminpanel\Modules\Debug\QueryInformation::class,
            ],
        ],
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['adminPanel_save']
    = \TYPO3\CMS\Adminpanel\Controller\AjaxController::class . '::saveDataAction';

$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG][\TYPO3\CMS\Adminpanel\Log\InMemoryLogWriter::class] = [];

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['adminpanel_requestcache'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['adminpanel_requestcache'] = [];
}
