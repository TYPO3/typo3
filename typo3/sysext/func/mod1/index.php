<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
/**
 * Module: Advanced functions
 * Advanced Functions related to pages
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require 'conf.php';
require $BACK_PATH . 'init.php';
$LANG->includeLLFile('EXT:lang/locallang_mod_web_func.xlf');
$BE_USER->modAccess($MCONF, 1);
/*
 * @deprecated since 6.0, the classname SC_mod_web_func_index and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/func/Classes/Controller/PageFunctionsController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('func') . 'Classes/Controller/PageFunctionsController.php';
// Make instance:
/** @var $SOBE \TYPO3\CMS\Func\Controller\PageFunctionsController */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Func\\Controller\\PageFunctionsController');
$SOBE->init();
// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
// Checking for first level external objects
$SOBE->checkExtObj();
// Repeat Include files! - if any files has been added by second-level extensions
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
// Checking second level external objects
$SOBE->checkSubExtObj();
$SOBE->main();
$SOBE->printContent();
?>