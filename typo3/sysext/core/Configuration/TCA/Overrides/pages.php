<?php

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

// 'sys_language_uid' has exclude=true by default, unset this for pages
unset($GLOBALS['TCA']['pages']['columns']['sys_language_uid']['exclude']);

// transOrigPointerField needs an adaptions on pages table, deviating from default:
// sys_language_uid = -1 is not allowed.
$GLOBALS['TCA']['pages']['columns']['l10n_parent']['config']['foreign_table_where'] = 'AND {#pages}.{#uid}=###CURRENT_PID### AND {#pages}.{#sys_language_uid} = 0';
