<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasper@typo3.com)
 *  (c) 2004-2013 Stanislas Rolland <typo3(arobas)jbr.ca>
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
 * Displays image selector for the RTE
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 * @author 	Stanislas Rolland <typo3(arobas)jbr.ca>
 */
error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));
unset($MCONF);
require 'conf.php';
require $BACK_PATH . 'init.php';
$LANG->includeLLFile('EXT:lang/locallang_TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/mod4/locallang.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/htmlarea/locallang_dialogs.xml');
/*
 * @deprecated since 6.0, the classname tx_rtehtmlarea_SC_select_image and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/rtehtmlarea/Classes/Controller/SelectImageController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rtehtmlarea') . 'Classes/Controller/SelectImageController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\Controller\\SelectImageController');
$SOBE->main();
$SOBE->printContent();
?>