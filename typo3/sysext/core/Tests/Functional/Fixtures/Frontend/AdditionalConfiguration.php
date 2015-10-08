<?php
defined('TYPO3_MODE') or die();

// You may add PHP code here, wich is executed on every request after the configuration is loaded.
// The code here should only manipulate TYPO3_CONF_VARS for example to set the database configuration
// dependent to the requested environment.

// $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitConfirmationOfTranslation'] = TRUE;

$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;

// Register hooks for frontend test
if (TYPO3_MODE === 'FE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']['FunctionalTest'] =
        \TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Hook\FrontendUserHandler::class . '->initialize';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']['FunctionalTest'] =
        \TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Hook\BackendUserHandler::class . '->initialize';
}
