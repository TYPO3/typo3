<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <typo3@ringerge.org>
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
 * Sys_note repository
 *
 * @package TYPO3
 * @subpackage sys_note
 * @author Georg Ringer <typo3@ringerge.org>
 */
class Tx_SysNote_Domain_Repository_SysNoteRepository {

	/**
	 * Find all sys_notes by a given pidlist
	 *
	 * @param string $pidlist comma separated list of pids
	 * @return array records
	 */
	public function findAllByPidList($pidlist) {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_note',
			'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($pidlist) . ')
					AND (personal=0 OR cruser=' . intval($GLOBALS['BE_USER']->user['uid']) . ')' .
				t3lib_BEfunc::deleteClause('sys_note'),
			'',
			'sorting'
		);

		foreach ($records as $key => $record) {
			$records[$key]['tstamp'] = new DateTime('@' . $record['tstamp']);
			$records[$key]['author'] = t3lib_BEfunc::getRecord('be_users', $record['cruser']);
		}

		return $records;
	}

}
?>