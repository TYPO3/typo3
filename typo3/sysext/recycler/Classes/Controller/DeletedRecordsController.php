<?php
namespace TYPO3\CMS\Recycler\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Julian Kleinhans <typo3@kj187.de>
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
 * Deleted Records View
 *
 * @author Erik Frister <erik_frister@otq-solutions.com>
 * @author Julian Kleinhans <typo3@kj187.de>
 */
class DeletedRecordsController {

	/**
	 * Transforms the rows for the deleted Records into the Array View necessary for ExtJS Ext.data.ArrayReader
	 *
	 * @param array     $rows   Array with table as key and array with all deleted rows
	 * @param integer	$totalDeleted: Number of deleted records in total, for PagingToolbar
	 * @return string   JSON Array
	 */
	public function transform($deletedRowsArray, $totalDeleted) {
		$total = 0;
		$jsonArray = array(
			'rows' => array()
		);
		// iterate
		if (is_array($deletedRowsArray) && count($deletedRowsArray) > 0) {
			foreach ($deletedRowsArray as $table => $rows) {
				$total += count($deletedRowsArray[$table]);
				foreach ($rows as $row) {
					$backendUser = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']], 'username', '', FALSE);
					$jsonArray['rows'][] = array(
						'uid' => $row['uid'],
						'pid' => $row['pid'],
						'table' => $table,
						'crdate' => \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['crdate']]),
						'tstamp' => \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['tstamp']]),
						'owner' => htmlspecialchars($backendUser['username']),
						'owner_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']],
						'tableTitle' => \TYPO3\CMS\Recycler\Utility\RecyclerUtility::getUtf8String($GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
						'title' => htmlspecialchars(\TYPO3\CMS\Recycler\Utility\RecyclerUtility::getUtf8String(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row))),
						'path' => \TYPO3\CMS\Recycler\Utility\RecyclerUtility::getRecordPath($row['pid'])
					);
				}
			}
		}
		$jsonArray['total'] = $totalDeleted;
		return json_encode($jsonArray);
	}

}


?>