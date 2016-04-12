<?php
defined('TYPO3_MODE') or die();

// Activate t3editor for sys_template config
if (is_array($GLOBALS['TCA']['pages']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['pages']['columns']['TSconfig']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['pages']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
