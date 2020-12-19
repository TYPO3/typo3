<?php

defined('TYPO3') or die();

// Activate t3editor for pages TSconfig
if (is_array($GLOBALS['TCA']['pages']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['pages']['columns']['TSconfig']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['pages']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
