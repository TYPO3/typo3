<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Julian Kleinhans <typo3@kj187.de>
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

require_once(t3lib_extMgm::extPath('recycler', 'classes/helper/class.tx_recycler_helper.php'));

/**
 * Deleted Records View
 *
 * @author  Erik Frister <erik_frister@otq-solutions.com>
 * @author	Julian Kleinhans <typo3@kj187.de>
 * @package	TYPO3
 * @subpackage	tx_recycler
 * @version $Id$
 **/
class tx_recycler_view_deletedRecords {

	/**
	 * Transforms the rows for the deleted Records into the Array View necessary for ExtJS Ext.data.ArrayReader
	 *
	 * @param array     $rows   Array with table as key and array with all deleted rows
	 * @param integer	$totalDeleted: Number of deleted records in total, for PagingToolbar
	 * @return string   JSON Array
	 **/
	public function transform ($deletedRowsArray, $totalDeleted) {
		$total = 0;

		$jsonArray = array(
			'rows'	=> array(),
		);

			// iterate
		if (is_array($deletedRowsArray) && count($deletedRowsArray) > 0) {
			foreach($deletedRowsArray as $table => $rows) {
				$total += count($deletedRowsArray[$table]);

				foreach($rows as $row) {
					$backendUser = t3lib_BEfunc::getRecord('be_users', $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']], 'username', '', FALSE);
					$jsonArray['rows'][] = array(
						'uid'	=> $row['uid'],
						'pid'	=> $row['pid'],
						'table'	=> $table,
						'crdate' => date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row[$GLOBALS['TCA'][$table]['ctrl']['crdate']]),
						'tstamp' => date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row[$GLOBALS['TCA'][$table]['ctrl']['tstamp']]),
						'owner' => $backendUser['username'],
						'owner_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']],
						'tableTitle' => tx_recycler_helper::getUtf8String(
							$GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'])
						),
						'title'	=> tx_recycler_helper::getUtf8String(
							t3lib_BEfunc::getRecordTitle($table, $row)
						),
						'path'	=> tx_recycler_helper::getRecordPath($row['pid']),
					);
				}
			}
		}

		$jsonArray['total'] = $totalDeleted;
		return json_encode($jsonArray);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/view/class.tx_recycler_view_deletedRecords.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/view/class.tx_recycler_view_deletedRecords.php']);
}

?>