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
 * Colorpicker wizard
 *
 * Revised for TYPO3 3.7 May/2004 by Kasper Skårhøj
 *
 * @author Mathias Schreiber <schreiber@wmdb.de>
 * @author Peter Kühn <peter@kuehn.com>
 * @author Kasper Skårhøj <typo3@typo3.com>
 */
$GLOBALS['BACK_PATH'] = '';
require 'init.php';
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_wizards.xlf');
/*
 * @deprecated since 6.0, the classname SC_wizard_colorpicker and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/Wizard/ColorpickerController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/Wizard/ColorpickerController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\Wizard\\ColorpickerController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>