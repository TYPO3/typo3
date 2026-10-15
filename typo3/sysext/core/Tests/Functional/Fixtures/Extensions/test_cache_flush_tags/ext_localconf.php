<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['test_cache_flush_tags'] = [
    'backend' => Typo3DatabaseBackend::class,
    'groups' => ['pages'],
];
