<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
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
 * Include file extending localRecordList for DBAL compatibility
 *
 * $Id: class.ux_db_list_extra.php 25913 2009-10-27 14:20:41Z xperseguers $
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 */

/**
 * Child class for rendering of Web > List (not the final class. see class.db_list_extra)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 * @package TYPO3
 * @subpackage DBAL
 */
class ux_localRecordList extends localRecordList {

	/**
	 * Creates part of query for searching after a word ($this->searchString) fields in input table
	 *
	 * DBAL specific: no LIKE for numeric fields, in this case "uid" (breaks on Oracle)
	 *                no LIKE for BLOB fields, skip
	 *
	 * @param	string		Table, in which the fields are being searched.
	 * @return	string		Returns part of WHERE-clause for searching, if applicable.
	 */
	function makeSearchString($table) {
			// Make query, only if table is valid and a search string is actually defined:
		if ($GLOBALS['TCA'][$table] && $this->searchString) {

				// Loading full table description - we need to traverse fields:
			t3lib_div::loadTCA($table);

				// Initialize field array:
			$sfields = array();
			$or = '';

				// add the uid only if input is numeric, cast to int
			if (is_numeric($this->searchString)) {
				$queryPart = ' AND (uid=' . (int)$this->searchString . ' OR ';
			} else {
				$queryPart = ' AND (';
			}

			if ($GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8')) {
				foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $info) {
					if ($GLOBALS['TYPO3_DB']->cache_fieldType[$table][$fieldName]['metaType'] === 'B') {
						// skip, LIKE is not supported on BLOB columns...
					} elseif ($info['config']['type'] === 'text' || ($info['config']['type'] === 'input' && !preg_match('/date|time|int/', $info['config']['eval']))) {
						$queryPart .= $or . $fieldName . ' LIKE \'%' . $GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table) . '%\'';
						$or = ' OR ';
					}
				}
			} else {
					// Traverse the configured columns and add all columns that can be searched
				foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $info) {
					if ($info['config']['type'] === 'text' || ($info['config']['type'] === 'input' && !preg_match('/date|time|int/', $info['config']['eval']))) {
						$sfields[] = $fieldName;
					}
				}

					// If search-fields were defined (and there always are) we create the query:
				if (count($sfields)) {
					$like = ' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table) . '%\'';		// Free-text
					$queryPart .= implode($like . ' OR ', $sfields) . $like;
				}
			}

				// Return query:
			return $queryPart . ')';
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_db_list_extra.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_db_list_extra.php']);
}

?>