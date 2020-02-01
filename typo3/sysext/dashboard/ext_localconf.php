<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
    if (TYPO3_MODE === 'BE') {
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Imaging\IconRegistry::class
        );
        $icons = [
            'dashboard-default' => 'dashboard-default.svg',
            'dashboard-empty' => 'dashboard-empty.svg',
            'dashboard-cta' => 'dashboard-cta.svg',
            'dashboard-typo3' => 'dashboard-typo3.svg',
            'dashboard-documentation' => 'dashboard-documentation.svg',
            'mimetypes-x-be_dashboard' => 'mimetypes-x-be_dashboard.svg',
        ];
        foreach ($icons as $iconIdentifier => $iconFile) {
            $iconRegistry->registerIcon(
                $iconIdentifier,
                \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                ['source' => 'EXT:dashboard/Resources/Public/Icons/' . $iconFile]
            );
        }

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
