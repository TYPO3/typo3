<?php
namespace TYPO3\CMS\Dbal\RecordList;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Include file extending localRecordList for DBAL compatibility
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 */
/**
 * Child class for rendering of Web > List (not the final class)
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 */
class DatabaseRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList {

	/**
	 * Creates part of query for searching after a word ($this->searchString) fields in input table
	 *
	 * DBAL specific: no LIKE for numeric fields, in this case "uid" (breaks on Oracle)
	 * no LIKE for BLOB fields, skip
	 *
	 * @param 	string		Table, in which the fields are being searched.
	 * @return 	string		Returns part of WHERE-clause for searching, if applicable.
	 * @todo Define visibility
	 */
	public function makeSearchString($table) {
		// Make query, only if table is valid and a search string is actually defined:
		if ($GLOBALS['TCA'][$table] && $this->searchString) {
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

					} elseif ($info['config']['type'] === 'text' || $info['config']['type'] === 'input' && !preg_match('/date|time|int/', $info['config']['eval'])) {
						$queryPart .= $or . $fieldName . ' LIKE \'%' . $GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table) . '%\'';
						$or = ' OR ';
					}
				}
			} else {
				// Traverse the configured columns and add all columns that can be searched
				foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $info) {
					if ($info['config']['type'] === 'text' || $info['config']['type'] === 'input' && !preg_match('/date|time|int/', $info['config']['eval'])) {
						$sfields[] = $fieldName;
					}
				}
				// If search-fields were defined (and there always are) we create the query:
				if (count($sfields)) {
					$like = ' LIKE \'%' . $GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table) . '%\'';
					// Free-text
					$queryPart .= implode(($like . ' OR '), $sfields) . $like;
				}
			}
			// Return query:
			return $queryPart . ')';
		}
	}

}
