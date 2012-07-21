<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Class for generation of the module menu.
 * Will make the vertical, horizontal, selectorbox based menus AND the "about modules" display.
 * Basically it traverses the module structure and generates output based on that.
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant content
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since TYPO3 4.6, this file will be removed in TYPO3 6.0.
 * 		The TYPO3 backend is using typo3/backend.php with less frames,
 * 		which makes this file obsolete.
 */

t3lib_div::deprecationLog('alt_menu_functions.inc is deprecated since TYPO3 4.6, this file will be removed in TYPO3 6.0. The TYPO3 backend is using typo3/backend.php with less frames, which makes this file obsolete.');

/**
 * Outdated class with menu functions
 *
 * This is kept as compatibility layer in case it's XCLASSed by any extension.
 * Successor: tx_aboutmodules_Functions
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @deprecated since 4.6, well be removed in 6.0
 * @see tx_aboutmodules_Functions
 */
class alt_menu_functions extends tx_aboutmodules_Functions {
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.alt_menu_functions.inc'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.alt_menu_functions.inc']);
}
?>