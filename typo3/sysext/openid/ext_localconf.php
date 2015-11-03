<?php
defined('TYPO3_MODE') or die();

// Register OpenID processing service with TYPO3
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'openid',
    'auth',
    'tx_openid_service_process',
    array(
        'title' => 'OpenID Authentication',
        'description' => 'OpenID processing login information service for Frontend and Backend',
        'subtype' => 'processLoginDataBE,processLoginDataFE',
        'available' => true,
        'priority' => 35,
        // Must be lower than for \TYPO3\CMS\Sv\AuthenticationService (50) to let other processing take place before
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'className' => \FoT3\Openid\OpenidService::class
    )
);

// Register OpenID authentication service with TYPO3
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'openid',
    'auth',
    'tx_openid_service',
    array(
        'title' => 'OpenID Authentication',
        'description' => 'OpenID authentication service for Frontend and Backend',
        'subtype' => 'getUserFE,authUserFE,getUserBE,authUserBE',
        'available' => true,
        'priority' => 75,
        // Must be higher than for \TYPO3\CMS\Sv\AuthenticationService (50) or \TYPO3\CMS\Sv\AuthenticationService will log failed login attempts
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'className' => \FoT3\Openid\OpenidService::class
    )
);

// Register eID script that performs final FE user authentication. It will be called by the OpenID provider
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_openid'] = 'EXT:openid/class.tx_openid_eid.php';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck'][\FoT3\Openid\OpenidModuleSetup::class] = '';

// Use popup window to refresh login instead of the AJAX relogin:
$GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] = 1;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416748] = array(
    'provider' => \FoT3\Openid\LoginProvider\OpenIdLoginProvider::class,
    'sorting' => 25,
    'icon-class' => 'fa-openid',
    'label' => 'LLL:EXT:openid/Resources/Private/Language/locallang.xlf:login.link'
);
