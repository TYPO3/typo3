<?php

defined('TYPO3') or die();

// Activate t3editor for be_users TSconfig
if (is_array($GLOBALS['TCA']['be_users']['columns']['TSconfig']['config'])) {
    $GLOBALS['TCA']['be_users']['columns']['TSconfig']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['be_users']['columns']['TSconfig']['config']['format'] = 'typoscript';
}
