<?php

defined('TYPO3') or die();

// Activate code editor for be_users TSconfig
if (is_array($GLOBALS['TCA']['be_users']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['be_users']['columns']['TSconfig']['config']['renderType'] = 'codeEditor';
    $GLOBALS['TCA']['be_users']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
