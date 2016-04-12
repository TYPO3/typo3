<?php
defined('TYPO3_MODE') or die();

// Activate t3editor for sys_template config
if (is_array($GLOBALS['TCA']['be_users']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['be_users']['columns']['TSconfig']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['be_users']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
