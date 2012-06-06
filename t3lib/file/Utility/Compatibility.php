<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Andreas Wolf <andreas.wolf@typo3.org>
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
 * Compatibility tools for FAL.
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Utility_Compatibility {
	/**
	 * Returns TRUE if the given field from the given table has been migrated by the migration wizard. Use this to e.g.
	 * check if you should use FAL-compatible code in your extension or still access files the old way.
	 *
	 * @param string $table
	 * @param string $field
	 * @return bool
	 */
	public static function isFieldMigrated($table, $field) {
		$migratedFields = &$GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard'];

		return (isset($migratedFields) && t3lib_div::inList($migratedFields, $table . ':' . $field));
	}


}