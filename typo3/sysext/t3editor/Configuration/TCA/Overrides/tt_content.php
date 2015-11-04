<?php
defined('TYPO3_MODE') or die();

// Activate t3editor for tt_content type HTML if this type exists
if (is_array($GLOBALS['TCA']['tt_content']['types']['html'])) {
    if (!is_array($GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides'])) {
        $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides'] = array();
    }
    if (!is_array($GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext'])) {
        $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext'] = array();
    }
    $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['defaultExtras'] = 'nowrap:wizards[t3editor]';
    if (!is_array($GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config'])) {
        $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config'] = array();
    }
    $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config']['renderType'] = 't3editor';
    $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config']['format'] = 'html';
}
