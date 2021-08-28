<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1435433112] = \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class;
// Register update signal to update the number of open documents
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['OpendocsController::updateNumber'] = \TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem::class . '->updateNumberOfOpenDocsHook';
