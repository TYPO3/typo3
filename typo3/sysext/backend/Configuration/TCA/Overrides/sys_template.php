<?php

defined('TYPO3') or die();

// Activate code editor for sys_template constants
if (is_array($GLOBALS['TCA']['sys_template']['columns']['constants']['config'])) {
    $GLOBALS['TCA']['sys_template']['columns']['constants']['config']['renderType'] = 'codeEditor';
    $GLOBALS['TCA']['sys_template']['columns']['constants']['config']['format'] = 'typoscript';
}

// Activate code editor for sys_template config
if (is_array($GLOBALS['TCA']['sys_template']['columns']['config']['config'])) {
    $GLOBALS['TCA']['sys_template']['columns']['config']['config']['renderType'] = 'codeEditor';
    $GLOBALS['TCA']['sys_template']['columns']['config']['config']['format'] = 'typoscript';
}
