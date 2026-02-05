<?php

declare(strict_types=1);

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders;
use TYPO3\CMS\Redirects\Evaluation\SourceHost;
use TYPO3\CMS\Redirects\Form\Element\QrCodeElement;
use TYPO3\CMS\Redirects\Form\Element\RenderCreationInformation;
use TYPO3\CMS\Redirects\Form\Element\ShortUrlElement;
use TYPO3\CMS\Redirects\Form\FieldControl\ShortUrlGenerator;
use TYPO3\CMS\Redirects\FormDataProvider\QrCodeSourceHostDataProvider;
use TYPO3\CMS\Redirects\FormDataProvider\ShortUrlDataProvider;
use TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider;
use TYPO3\CMS\Redirects\Hooks\DataHandlerCacheFlushingHook;
use TYPO3\CMS\Redirects\Hooks\DataHandlerPermissionGuardHook;
use TYPO3\CMS\Redirects\Hooks\DataHandlerSlugUpdateHook;
use TYPO3\CMS\Redirects\Hooks\DispatchNotificationHook;
use TYPO3\CMS\Redirects\Hooks\HandleNewQrCodeRecord;
use TYPO3\CMS\Redirects\Hooks\HandleNewShortUrlRecord;

defined('TYPO3') or die();

// Rebuild cache in DataHandler on changing / inserting / adding redirect records
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['redirects'] = DataHandlerCacheFlushingHook::class . '->rebuildRedirectCacheIfNecessary';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects'] = DataHandlerSlugUpdateHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects-qrcode'] = HandleNewQrCodeRecord::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects-short-url'] = HandleNewShortUrlRecord::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirectsAccessGuard'] = DataHandlerPermissionGuardHook::class;

// Inject sys_domains into valuepicker form
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
[ValuePickerItemDataProvider::class] = [
    'depends' => [
        TcaInputPlaceholders::class,
    ],
];

// Renders Redirect creation information. i.e. backend username and creation date.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1761573166] = [
    'nodeName' => 'creationInformation',
    'priority' => 40,
    'class' => RenderCreationInformation::class,
];

// Renders QR code with options
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1764867024] = [
    'nodeName' => 'qrCode',
    'priority' => 40,
    'class' => QrCodeElement::class,
];

// Renders Short URL element
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1770156231] = [
    'nodeName' => 'shortUrl',
    'priority' => 40,
    'class' => ShortUrlElement::class,
];

// Renders shortUrlGenerator field control
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1770204638] = [
    'nodeName' => 'shortUrlGenerator',
    'priority' => 40,
    'class' => ShortUrlGenerator::class,
];

// Set "source_host" to "readOnly" for the sys_redirects of type "qrcode"
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
[QrCodeSourceHostDataProvider::class] = [
    'depends' => [
        TcaInputPlaceholders::class,
    ],
];

// Set "short_url" to "readOnly" for the sys_redirects of type "short_url"
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
[ShortUrlDataProvider::class] = [
    'depends' => [
        TcaInputPlaceholders::class,
    ],
];

// Add validation call for form field source_host and source_path
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][SourceHost::class] = '';

// Register update signal to send delayed notifications
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['redirects:slugChanged'] = DispatchNotificationHook::class . '->dispatchNotification';
