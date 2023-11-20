<?php

defined('TYPO3') or die();

// Activate code editor for pages TSconfig
if (is_array($GLOBALS['TCA']['pages']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['pages']['columns']['TSconfig']['config']['renderType'] = 'codeEditor';
    $GLOBALS['TCA']['pages']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
