<?php
namespace TYPO3\CMS\Recycler\Domain\Model;

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
 * Model class for the 'recycler' extension.
 *
 * @author 	Julian Kleinhans <typo3@kj187.de>
 */
class Tables {

	/**
	 * Get tables for menu example
	 *
	 * @param 	string		$format: Return format (example: json)
	 * @param 	boolean		$withAllOption: 0 no, 1 return tables with a "all" option
	 * @param 	integer		$id: UID from selected page
	 * @param 	integer		$depth: How many levels recursive
	 * @return 	string		The tables to be displayed
	 */
	public function getTables($format, $withAllOption = 0, $startUid, $depth = 0) {
		$deletedRecordsTotal = 0;
		$tables = array();
		foreach (array_keys($GLOBALS['TCA']) as $tableName) {
			$deletedField = \TYPO3\CMS\Recycler\Utility\RecyclerUtility::getDeletedField($tableName);
			if ($deletedField) {
				// Determine whether the table has deleted records:
				$deletedCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $tableName, $deletedField . '<>0');
				if ($deletedCount) {
					$deletedDataObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Domain\\Model\\DeletedRecords');
					$deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
					if (isset($deletedData[$tableName])) {
						if ($deletedRecordsInTable = count($deletedData[$tableName])) {
							$deletedRecordsTotal += $deletedRecordsInTable;
							$tables[] = array(
								$tableName,
								$deletedRecordsInTable,
								$tableName,
								\TYPO3\CMS\Recycler\Utility\RecyclerUtility::getUtf8String($GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']))
							);
						}
					}
				}
			}
		}
		$jsonArray = $tables;
		if ($withAllOption) {
			array_unshift($jsonArray, array(
				'',
				$deletedRecordsTotal,
				'',
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/mod1/locallang.xml:label_alltables')
			));
		}
		$output = json_encode($jsonArray);
		return $output;
	}

}


?>