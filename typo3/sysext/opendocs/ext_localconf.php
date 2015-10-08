<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1435433112] = \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class;
}
