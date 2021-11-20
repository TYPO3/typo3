<?php

declare(strict_types=1);

use TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem;

defined('TYPO3') or die();

// Register update signal to update the number of open documents
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['OpendocsController::updateNumber'] = OpendocsToolbarItem::class . '->updateNumberOfOpenDocsHook';
