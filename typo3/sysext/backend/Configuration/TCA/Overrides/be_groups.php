<?php

defined('TYPO3') or die();

// Activate code editor for be_groups TSconfig
if (is_array($GLOBALS['TCA']['be_groups']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['be_groups']['columns']['TSconfig']['config']['renderType'] = 'codeEditor';
    $GLOBALS['TCA']['be_groups']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
