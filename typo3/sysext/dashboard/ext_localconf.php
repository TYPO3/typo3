<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function () {
    if (TYPO3_MODE === 'BE') {
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dashboard_rss'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dashboard_rss'] = [
                'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                'options' => [
                    'defaultLifetime' => 900,
                ],
            ];
        }

        /**
         * Set starting module to dashboard if users didn't change it
         */
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUC']['startModule'] = 'web_dashboard';
    }
});
