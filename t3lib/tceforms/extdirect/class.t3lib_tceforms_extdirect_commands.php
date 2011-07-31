<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kay Strobach <typo3@kay-strobach.de>
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
 * Commands tceforms
 *
 * @author Kay Strobach <typo3@kay-strobach.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tceforms_extdirect_Commands {
	/**
	 * Returns the tstamp of a record selected by its table and uid
	 * Additionally this function checks whether the record is opened for editing
	 *
	 * @param string $table The table where you want to search the record
	 * @param integer $uid The uid of the searched record
	 * 
	 * @return	array 
	 */
	public static function getRecordLastchange($table, $uid) {
			// build lastchange
		$record = t3lib_BEfunc::getRecord($table,$uid);
		$return = array(
			'tstamp' => $record['tstamp'],
			'table' => $table,
			'uid' => $uid,
			'title' => t3lib_BEfunc::getRecordTitle($table, $record, TRUE, TRUE),
			'tableTranslated' => $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE),
		);
		return $return;
	}
}