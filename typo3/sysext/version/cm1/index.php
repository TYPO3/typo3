<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require 'conf.php';
require $BACK_PATH . 'init.php';
require $BACK_PATH . 'template.php';
$GLOBALS['LANG']->includeLLFile('EXT:version/locallang.xml');
// DEFAULT initialization of a module [END]
require_once '../ws/class.wslib.php';
/*
 * @deprecated since 6.0, the classname tx_version_cm1 and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/version/Classes/Controller/VersionModuleController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('version') . 'Classes/Controller/VersionModuleController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Controller\\VersionModuleController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>