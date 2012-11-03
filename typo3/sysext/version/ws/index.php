<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
// Initialize module:
unset($MCONF);
require 'conf.php';
require $GLOBALS['BACK_PATH'] . 'init.php';
require $GLOBALS['BACK_PATH'] . 'template.php';
$GLOBALS['BE_USER']->modAccess($MCONF, 1);
// Include libraries of various kinds used inside:
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_mod_user_ws.xml');
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xml');
require_once 'class.wslib.php';
require_once 'class.wslib_gui.php';
/*
 * @deprecated since 6.0, the classname SC_mod_user_ws_index and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/version/Classes/Controller/WorkspaceModuleController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('version') . 'Classes/Controller/WorkspaceModuleController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Controller\\WorkspaceModuleController');
$SOBE->execute();
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>