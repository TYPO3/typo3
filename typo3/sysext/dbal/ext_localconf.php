<?php
defined('TYPO3_MODE') or die();

// Main xclass
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class] = [
    'className' => \TYPO3\CMS\Dbal\Database\DatabaseConnection::class
];

// Register caches if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'] = [
        'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
        'groups' => []
    ];
}
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal_phpcode'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal_phpcode'] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
        'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
        'options' => [
            'defaultLifetime' => 0,
        ],
        'groups' => ['system']
    ];
}
