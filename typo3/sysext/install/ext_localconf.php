<?php
defined('TYPO3_MODE') or die();

// Do not delete this wizard. This makes sure new installations get the TER repository set in the database.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['extensionManagerTables']
    = \TYPO3\CMS\Install\Updates\ExtensionManagerTables::class;

// TYPO3 CMS 8
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['wizardDoneToRegistry']
    = \TYPO3\CMS\Install\Updates\WizardDoneToRegistry::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['startModuleUpdate']
    = \TYPO3\CMS\Install\Updates\StartModuleUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['frontendUserImageUpdateWizard']
    = \TYPO3\CMS\Install\Updates\FrontendUserImageUpdateWizard::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['databaseRowsUpdateWizard']
    = \TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['commandLineBackendUserRemovalUpdate']
    = \TYPO3\CMS\Install\Updates\CommandLineBackendUserRemovalUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['fillTranslationSourceField']
    = \TYPO3\CMS\Install\Updates\FillTranslationSourceField::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sectionFrameToFrameClassUpdate']
    = \TYPO3\CMS\Install\Updates\SectionFrameToFrameClassUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['splitMenusUpdate']
    = \TYPO3\CMS\Install\Updates\SplitMenusUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['bulletContentElementUpdate']
    = \TYPO3\CMS\Install\Updates\BulletContentElementUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['uploadContentElementUpdate']
    = \TYPO3\CMS\Install\Updates\UploadContentElementUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['migrateFscStaticTemplateUpdate']
    = \TYPO3\CMS\Install\Updates\MigrateFscStaticTemplateUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['fileReferenceUpdate']
    = \TYPO3\CMS\Install\Updates\FileReferenceUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['migrateFeSessionDataUpdate']
    = \TYPO3\CMS\Install\Updates\MigrateFeSessionDataUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['compatibility7Extension']
    = \TYPO3\CMS\Install\Updates\Compatibility7ExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['formLegacyExtractionUpdate']
    = \TYPO3\CMS\Install\Updates\FormLegacyExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['rtehtmlareaExtension']
    = \TYPO3\CMS\Install\Updates\RteHtmlAreaExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysLanguageSorting']
    = \TYPO3\CMS\Install\Updates\LanguageSortingUpdate::class;

// Add update wizards below this line
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['typo3DbLegacyExtension']
    = \TYPO3\CMS\Install\Updates\Typo3DbExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['funcExtension']
    = \TYPO3\CMS\Install\Updates\FuncExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['pagesUrltypeField']
    = \TYPO3\CMS\Install\Updates\MigrateUrlTypesInPagesUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['separateSysHistoryFromLog']
    = \TYPO3\CMS\Install\Updates\SeparateSysHistoryFromSysLogUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['rdctExtension']
    = \TYPO3\CMS\Install\Updates\RedirectExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['cshmanualBackendUsers']
    = \TYPO3\CMS\Install\Updates\BackendUserStartModuleUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['pagesLanguageOverlay']
    = \TYPO3\CMS\Install\Updates\MigratePagesLanguageOverlayUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['pagesLanguageOverlayBeGroupsAccessRights']
    = \TYPO3\CMS\Install\Updates\MigratePagesLanguageOverlayBeGroupsAccessRights::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendLayoutIcons']
    = \TYPO3\CMS\Install\Updates\BackendLayoutIconUpdateWizard::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['redirects']
    = \TYPO3\CMS\Install\Updates\RedirectsExtensionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['adminpanelExtension']
    = \TYPO3\CMS\Install\Updates\AdminPanelInstall::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['pagesSlugs']
    = \TYPO3\CMS\Install\Updates\PopulatePageSlugs::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['argon2iPasswordHashes']
    = \TYPO3\CMS\Install\Updates\Argon2iPasswordHashes::class;

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

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)
    ->connect(
        \TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class,
        'loadMessages',
        \TYPO3\CMS\Install\SystemInformation\Typo3VersionMessage::class,
        'appendMessage'
    );
