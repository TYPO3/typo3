<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Adds extra fields into 'media' flexform
 *
 * @package TYPO3
 * @subpackage cms
 */

class tx_cms_mediaItems implements t3lib_Singleton {

	/**
	 * Load extra render types if they exist
	 *
	 * @param	array		$params: Existing types by reference
	 * @param 	array		$conf: config array
	 */
	public function customMediaRenderTypes(&$params, $conf) {
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRenderTypes'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRenderTypes'] as $classRef) {
				$hookObj = t3lib_div::getUserObj($classRef);
				$hookObj->customMediaRenderTypes($params, $conf);
			}
		}


	}

	/**
	 * Load extra predefined media params if they exist
	 *
	 * @param	array		$params: Existing types by reference
	 * @param 	array		$conf: config array
	 */
	public function customMediaParams(&$params, $conf) {

		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaParams'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaParams'] as $classRef) {
				$hookObj = t3lib_div::getUserObj($classRef);
				$hookObj->customMediaParams($params, $conf);
			}
		}


	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/hooks/class.tx_cms_mediaitems.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/hooks/class.tx_cms_mediaitems.php']);
}
?>
