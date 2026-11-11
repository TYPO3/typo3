<?php

use TYPO3\CMS\Core\Schema\LanguageMarker;

defined('TYPO3') or die();

if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('seo')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'metatags', '--linebreak--, description', 'after:keywords');
}

// New pages are disabled by default
$GLOBALS['TCA']['pages']['columns']['hidden']['config']['default'] = 1;

// @todo: It's unclear if different start/end times for page localizations actually work throughout the system.
$GLOBALS['TCA']['pages']['columns']['starttime']['config']['behaviour']['allowLanguageSynchronization'] = true;
$GLOBALS['TCA']['pages']['columns']['endtime']['config']['behaviour']['allowLanguageSynchronization'] = true;

// 'editlock' has l10n_mode=exclude, note 'tt_content' does not have this ...
$GLOBALS['TCA']['pages']['columns']['editlock']['l10n_mode'] = 'exclude';

// 'fe_group' has l10n_mode=exclude, frontend access is evaluated on the default language page
$GLOBALS['TCA']['pages']['columns']['fe_group']['l10n_mode'] = 'exclude';

// transOrigPointerField needs an adaptions on pages table, deviating from default:
// sys_language_uid = -1 is not allowed.
$GLOBALS['TCA']['pages']['columns']['l10n_parent']['config']['foreign_table_where'] = 'AND {#pages}.{#uid}=###CURRENT_PID### AND {#pages}.{#sys_language_uid} = ' . LanguageMarker::DEFAULT_LANGUAGE;
