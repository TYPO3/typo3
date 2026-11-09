<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

defined('TYPO3') or die();

// Keys for shell completion of "lowlevel:inspect", short-lived as every TAB is a new process
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lowlevel_inspect'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lowlevel_inspect'] = [
        'frontend' => VariableFrontend::class,
        'backend' => FileBackend::class,
        'options' => [
            'defaultLifetime' => 60,
        ],
    ];
}
