<?php

declare(strict_types=1);

use TYPO3\CMS\SysNote\Hook\ButtonBarHook;
use TYPO3\CMS\SysNote\Hook\InfoModuleHook;
use TYPO3\CMS\SysNote\Hook\PageHook;

defined('TYPO3') or die();

// Hook into the page modules
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']['sys_note'] = PageHook::class . '->renderInHeader';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook']['sys_note'] = PageHook::class . '->renderInFooter';
// Hook into the info module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook']['sys_note'] = InfoModuleHook::class . '->render';
// Hook into the button bar
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['sys_note'] = ButtonBarHook::class . '->getButtons';
