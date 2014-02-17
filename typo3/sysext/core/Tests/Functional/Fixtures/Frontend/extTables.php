<?php
/**
 * Overriding $TCA
 *
 * The TYPO3 Configuration Array (TCA) is defined by the distributed tables.php and ext_tables.php files.
 * If you want to extend and/or modify its content, you can do so with scripts like this.
 * Or BETTER yet - with extensions like those found in the typo3conf/ext/ or typo3/ext/ folder.
 * Extensions are movable to other TYPO3 installations and provides a much better division between things! Use them!
 *
 * Information on how to set up tables is found in the document "Inside TYPO3" available as a PDF from where you downloaded TYPO3.
 *
 * Usage:
 * Just put this file to the location typo3conf/extTables.php and add this line to your typo3conf/localconf.php:
 * $typo_db_extTableDef_script = 'extTables.php';
 */

// Show copied pages records in frontend request
$GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = FALSE;
// Show copied tt_content records in frontend request
$GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = FALSE;
// Prepend label for copied sys_category records
$GLOBALS['TCA']['sys_category']['ctrl']['prependAtCopy'] = 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy';
// Prepend label for localized sys_category records
$GLOBALS['TCA']['sys_category']['columns']['title']['l10n_mode'] = 'prefixLangTitle';

?>