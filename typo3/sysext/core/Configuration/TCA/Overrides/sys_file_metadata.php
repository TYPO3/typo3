<?php

defined('TYPO3') or die();

// @todo: transOrigPointerField is configured differently. Needed? Possible to keep default?
$GLOBALS['TCA']['sys_file_metadata']['columns']['l10n_parent']['config'] = [
    'type' => 'group',
    'allowed' => 'sys_file_metadata',
    'size' => 1,
    'relationship' => 'manyToOne',
    'default' => 0,
];
