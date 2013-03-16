<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Robert Lemke (robert@typo3.org)
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
/**
 * Check connection wizard for ADO DB databases. For usage in a popup window.
 *
 * @author Robert Lemke <robert@typo3.org>
 */
// Build TYPO3 enviroment:
$BACK_PATH = '../../../typo3/';
define('TYPO3_MOD_PATH', 'sysext/adodb/');
require $BACK_PATH . 'init.php';
// Include ADODB library:
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('adodb') . 'adodb/adodb.inc.php';
// Include language labels:
$LANG->includeLLFile('EXT:adodb/locallang_wizard.xml');
/*
 * @deprecated since 6.0, the classname tx_adodb_checkconnectionwizard and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/adodb/Classes/View/CheckConnectionWizardView.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('adodb') . 'Classes/View/CheckConnectionWizardView.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Adodb\\View\\CheckConnectionWizardView');
$SOBE->main();
?>