<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \TYPO3\CMS\Adminpanel\Hooks\RenderHook::class . '->renderAdminPanel';

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
    ],
    'info' => [
        'module' => \TYPO3\CMS\Adminpanel\Modules\InfoModule::class,
        'after' => ['tsdebug'],
    ],
];
