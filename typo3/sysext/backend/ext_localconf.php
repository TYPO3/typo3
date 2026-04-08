<?php

declare(strict_types=1);

use TYPO3\CMS\Backend\Hooks\DataHandlerAuthenticationContext;
use TYPO3\CMS\Backend\Hooks\DataHandlerContentElementRestrictionHook;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416747] = [
    'provider' => UsernamePasswordLoginProvider::class,
    'sorting' => 50,
    'iconIdentifier' => 'actions-key',
    'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:login.link',
];

// Register search key shortcuts
$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['page'] = 'pages';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandlerAuthenticationContext::class;

// Giving the two hook registrations a unique name to allow unsetting this hook if it is not wanted.
// ext:container does this to apply own logic.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contentElementRestriction'] = DataHandlerContentElementRestrictionHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['contentElementRestriction'] = DataHandlerContentElementRestrictionHook::class;

// Initialize empty structure for backward compatibility with extensions
// that add fields via $GLOBALS['TYPO3_USER_SETTINGS']['columns'].
// Core settings are now defined in Configuration/TCA/Overrides/be_users.php.
// Access to settings should go through UserSettingsSchema which merges both sources.
// @deprecated since TYPO3 v14, remove in TYPO3 v15
$GLOBALS['TYPO3_USER_SETTINGS'] = [
    'columns' => [],
    'showitem' => '',
];
