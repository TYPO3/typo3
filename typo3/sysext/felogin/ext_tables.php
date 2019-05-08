<?php
defined('TYPO3_MODE') or die();

(static function (): void {
    $feloginExtbase = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\Features::class)
        ->isFeatureEnabled('felogin.extbase');

    if (!$feloginExtbase) {
        return;
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'TYPO3.CMS.Felogin',
        'Login',
        'Login: Login Form'
    );
})();
