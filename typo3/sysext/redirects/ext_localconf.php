<?php
defined('TYPO3_MODE') or die();

// Register hook into the frontend to check for a possible redirect
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']['redirects'] = \TYPO3\CMS\Redirects\Http\RedirectHandler::class . '->handle';

// Rebuild cache in DataHandler on changing / inserting / adding redirect records
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['redirects'] = \TYPO3\CMS\Redirects\Hooks\DataHandlerCacheFlushingHook::class . '->rebuildRedirectCacheIfNecessary';

// Inject sys_domains into valuepicker form
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
[\TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider::class] = [
    'depends' => [
        \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
    ],
];

// Add validation call for form field source_host and source_path
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\TYPO3\CMS\Redirects\Evaluation\SourcePath::class] = '';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\TYPO3\CMS\Redirects\Evaluation\SourceHost::class] = '';
