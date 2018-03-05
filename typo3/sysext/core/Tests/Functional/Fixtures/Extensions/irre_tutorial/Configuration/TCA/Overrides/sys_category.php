<?php

// Prepend label for copied sys_category records
$GLOBALS['TCA']['sys_category']['ctrl']['prependAtCopy'] = 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy';
// Prepend label for localized sys_category records
$GLOBALS['TCA']['sys_category']['columns']['title']['l10n_mode'] = 'prefixLangTitle';
