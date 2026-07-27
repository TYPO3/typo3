<?php

defined('TYPO3') or die();

// 'sys_language_uid' and 'hidden' are part of a hidden palette, there is no access rights configuration for them.
unset($GLOBALS['TCA']['sys_file_reference']['columns']['sys_language_uid']['exclude']);
unset($GLOBALS['TCA']['sys_file_reference']['columns']['hidden']['exclude']);

// @todo: transOrigPointerField is configured differently. Needed? Possible to keep default?
$GLOBALS['TCA']['sys_file_reference']['columns']['l10n_parent']['config'] = [
    'type' => 'group',
    'allowed' => 'sys_file_reference',
    'size' => 1,
    'relationship' => 'manyToOne',
    'default' => 0,
];
