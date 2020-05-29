<?php

defined('TYPO3_MODE') or die();

// Do not delete this wizard. This makes sure new installations get the TER repository set in the database.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['extensionManagerTables']
    = \TYPO3\CMS\Install\Updates\ExtensionManagerTables::class;

// v9->v10 wizards below this line
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['rsaauthExtension']
    = \TYPO3\CMS\Install\Updates\RsaauthExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['feeditExtension']
    = \TYPO3\CMS\Install\Updates\FeeditExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['taskcenterExtension']
    = \TYPO3\CMS\Install\Updates\TaskcenterExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysActionExtension']
    = \TYPO3\CMS\Install\Updates\SysActionExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['databaseRowsUpdateWizard']
    = \TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard::class;

// v10->v11 wizards below this line

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$icons = [
    'module-install-environment' => 'EXT:install/Resources/Public/Icons/module-install-environment.svg',
    'module-install-maintenance' => 'EXT:install/Resources/Public/Icons/module-install-maintenance.svg',
    'module-install-settings' => 'EXT:install/Resources/Public/Icons/module-install-settings.svg',
    'module-install-upgrade' => 'EXT:install/Resources/Public/Icons/module-install-upgrade.svg',
];

foreach ($icons as $iconIdentifier => $source) {
    $iconRegistry->registerIcon(
        $iconIdentifier,
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => $source]
    );
}

// Register report module additions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['typo3'][] = \TYPO3\CMS\Install\Report\InstallStatusReport::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['security'][] = \TYPO3\CMS\Install\Report\SecurityStatusReport::class;

// Only add the environment status report if not in CLI mode
if (!\TYPO3\CMS\Core\Core\Environment::isCli()) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['system'][] = \TYPO3\CMS\Install\Report\EnvironmentStatusReport::class;
}
