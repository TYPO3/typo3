<?php

defined('TYPO3') or die();

// Activate t3editor for be_groups TSconfig
if (is_array($GLOBALS['TCA']['be_groups']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['be_groups']['columns']['TSconfig']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['be_groups']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
