<?php
defined('TYPO3_MODE') or die();

// Activate t3editor for sys_template constants
if (is_array($GLOBALS['TCA']['sys_template']['columns']['constants']['config'])) {
    $GLOBALS['TCA']['sys_template']['columns']['constants']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['sys_template']['columns']['constants']['config']['format'] = 'typoscript';
}

// Activate t3editor for sys_template config
if (is_array($GLOBALS['TCA']['sys_template']['columns']['config']['config'])) {
    $GLOBALS['TCA']['sys_template']['columns']['config']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['sys_template']['columns']['config']['config']['format'] = 'typoscript';
}
