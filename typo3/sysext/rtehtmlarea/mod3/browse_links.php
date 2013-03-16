<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2005-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Adapted for htmlArea RTE by Stanislas Rolland
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));
unset($MCONF);
require 'conf.php';
require $BACK_PATH . 'init.php';
$LANG->includeLLFile('EXT:rtehtmlarea/mod3/locallang.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/htmlarea/locallang_dialogs.xml');
/*
 * @deprecated since 6.0, the classname tx_rtehtmlarea_SC_browse_links and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/rtehtmlarea/Classes/Controller/BrowseLinksController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rtehtmlarea') . 'Classes/Controller/BrowseLinksController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\Controller\\BrowseLinksController');
$SOBE->main();
$SOBE->printContent();
?>