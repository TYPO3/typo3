<?php
defined('TYPO3_MODE') or die();

// Add the service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService('rsaauth', 'auth', \TYPO3\CMS\Rsaauth\RsaAuthService::class, [
    'title' => 'RSA authentication',
    'description' => 'Authenticates users by using encrypted passwords',
    'subtype' => 'processLoginDataBE,processLoginDataFE',
    'available' => true,
    'priority' => 60,
    // tx_svauth_sv1 has 50, t3sec_saltedpw has 55. This service must have higher priority!
    'quality' => 60,
    // tx_svauth_sv1 has 50. This service must have higher quality!
    'os' => '',
    'exec' => '',
    // Do not put a dependency on openssh here or service loading will fail!
    'className' => \TYPO3\CMS\Rsaauth\RsaAuthService::class
]);

// Add hook for user setup module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['setupScriptHook']['rsaauth'] = \TYPO3\CMS\Rsaauth\Hook\UserSetupHook::class . '->getLoginScripts';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave']['rsaauth'] = \TYPO3\CMS\Rsaauth\Hook\UserSetupHook::class . '->decryptPassword';
// Add a hook to the FE login form (felogin system extension)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs']['rsaauth'] = \TYPO3\CMS\Rsaauth\Hook\FrontendLoginHook::class . '->loginFormHook';
// Add a hook to show Backend warnings
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages']['rsaauth'] = \TYPO3\CMS\Rsaauth\BackendWarnings::class;

// eID for FrontendLoginRsaPublicKey
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['RsaPublicKeyGenerationController'] = \TYPO3\CMS\Rsaauth\Controller\RsaPublicKeyGenerationController::class . '::processRequest';

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
    \TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::class,
    \TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::SIGNAL_getPageRenderer,
    \TYPO3\CMS\Rsaauth\Slot\UsernamePasswordProviderSlot::class,
    'getPageRenderer'
);

// Register automatic decryption in DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['rsaauth'] = \TYPO3\CMS\Rsaauth\Hook\DecryptionHook::class;

// Add own form element
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1436965601] = [
    'nodeName' => 'rsaInput',
    'priority' => '70',
    'class' => \TYPO3\CMS\Rsaauth\Form\Element\RsaInputElement::class,
];
