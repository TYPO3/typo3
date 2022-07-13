<?php

declare(strict_types=1);

use TYPO3\CMS\Install\Updates\BackendGroupsExplicitAllowDenyMigration;
use TYPO3\CMS\Install\Updates\BackendModulePermissionMigration;
use TYPO3\CMS\Install\Updates\BackendUserLanguageMigration;
use TYPO3\CMS\Install\Updates\CollectionsExtractionUpdate;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;
use TYPO3\CMS\Install\Updates\FeLoginModeExtractionUpdate;
use TYPO3\CMS\Install\Updates\ShortcutRecordsMigration;
use TYPO3\CMS\Install\Updates\SvgFilesSanitization;
use TYPO3\CMS\Install\Updates\SysFileMountIdentifierMigration;
use TYPO3\CMS\Install\Updates\SysLogChannel;
use TYPO3\CMS\Install\Updates\SysLogSerializationUpdate;

defined('TYPO3') or die();

// Row updater wizard scans all table rows for update jobs.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['databaseRowsUpdateWizard'] = DatabaseRowsUpdateWizard::class;

// v10->v11 wizards below this line
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['svgFilesSanitization'] = SvgFilesSanitization::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['shortcutRecordsMigration'] = ShortcutRecordsMigration::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['legacyCollectionsExtension'] = CollectionsExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendUserLanguage'] = BackendUserLanguageMigration::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysLogChannel'] = SysLogChannel::class;

// v11->v12 wizards below this line
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['feLoginModeExtension'] = FeLoginModeExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysLogSerialization'] = SysLogSerializationUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendGroupsExplicitAllowDenyMigration'] = BackendGroupsExplicitAllowDenyMigration::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysFileMountIdentifierMigration'] = SysFileMountIdentifierMigration::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendModulePermission'] = BackendModulePermissionMigration::class;
