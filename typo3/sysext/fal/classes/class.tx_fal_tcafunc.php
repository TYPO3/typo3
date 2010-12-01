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
 * File Abtraction Layer tcafunc
 *
 * @todo Andy Grunwald, 01.12.2010, matching the class nam econvention? new name tx_fal_TCAFunc ?
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_tcafunc {

	/**
	 * Get field TCA configuration (only "config" part) for File Abstraction Layer
	 * fields.
	 *
	 * @param	string		$fieldName			Field name
	 * @param	string		$tableName			Table name of field
	 * @param	string		$fieldToOverlay		Field name of field to replace. For example "media" in "pages" ("media" => "media_rel")
	 * @param	string		$allowedFileTypes	Comma list of allowed file extensions (e.g. "jpg,gif,png"). By default, it's allowing TYPO3_CONF_VARS[GFX][imagefile_ext].
	 * @param	array		$overrideArray		Array to override or add configuration options
	 * @return	array							Field TCA configuration "config" section for the field
	 */
	public static function getFileFieldTCAConfig($fieldName, $tableName, $fieldToOverlay = '', $allowedFileTypes = '', array $overrideArray = array()) {
		if(!$allowedFileTypes) {
			$allowedFileTypes = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
		}

		/**
		 * Write mapping overlay for tables.
		 * Syntax is:
		 * Table > fieldToOverlay => FAL field
		 * See code to get the idea
		 */
		if ($fieldToOverlay === '') {
			$fieldToOverlay = $fieldName;
		}
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$tableName][$fieldToOverlay] = $fieldName;

		$TCAFieldConfig = t3lib_div::array_merge_recursive_overrule(array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'sys_files',
			'internal_subtype' => 'file_record',
			'internal_subtype_allowed' => $allowedFileTypes,
			'prepend_tname' => 1,
			'show_thumbs' => 1,
			'size' => 5,
			'minitems' => 0,
			'MM_opposite_field' => 'file_usage',
			'MM' => 'sys_files_usage_mm',
			'MM_match_fields' => array('ident' => $fieldName),
		), $overrideArray);

		return $TCAFieldConfig;
	}

	/**
	 * Checks if the incomming $field in the incomming $table is a field for FAL
	 *
	 * @param	string	$field		Field name of TCA definition
	 * @param	string	$table		Table name of TCA definition
	 * @return	bool				DESCRIPTION
	 */
	public static function isFieldAFalField($field, $table) {
		$result = FALSE;

		$mappingArray = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$table];
		if (!is_array($mappingArray)) {
			return $result;
		}

		foreach ($mappingArray as $falField) {
			if ($field === $falField) {
				$result = TRUE;
				break;
			}
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_tcafunc.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_tcafunc.php']);
}
?>