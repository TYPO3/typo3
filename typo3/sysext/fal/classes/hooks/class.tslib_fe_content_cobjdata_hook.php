<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer: hook method for class.tslib_cobj
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id:  $
 */

class tx_fal_cobjdata_hook {

	/**
	 * modifies a database record in order to use fal records instead of images
	 *
	 *
	 * @param array $row 	The row
	 * @param string $table	the table
	 * @return void
	 */
	public function modifyDBRow(&$row, $table) {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$table])) {
			$repo = t3lib_div::makeInstance('tx_fal_Repository');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$table] as $overlayField => $fieldName) {
				$references = $repo->getFilesFromRelation($fieldName, $table, intval($row['uid']));
				$references = tx_fal_Helper::createCsvListOfFilepaths($references);
				$row[$fieldName] = $references;
				$row[$overlayField] = $references;
			};
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tslib_fe_content_cobjdata_hook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tslib_fe_content_cobjdata_hook.php']);
}
?>