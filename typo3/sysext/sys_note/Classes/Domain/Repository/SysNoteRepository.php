<?php
namespace TYPO3\CMS\SysNote\Domain\Repository;

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
 * @author Georg Ringer <typo3@ringerge.org>
 */
class SysNoteRepository {

	/**
	 * Find all sys_notes by a given pidlist
	 *
	 * @param string $pidlist comma separated list of pids
	 * @return array records
	 */
	public function findAllByPidList($pidlist) {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_note', 'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($pidlist) . ')
					AND (personal=0 OR cruser=' . intval($GLOBALS['BE_USER']->user['uid']) . ')' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_note'), '', 'sorting');
		// exec_SELECTgetRows can return NULL if the query failed. This is
		// transformed here to an empty array instead.
		if ($records === NULL) {
			$records = array();
		}
		foreach ($records as $key => $record) {
			$records[$key]['tstamp'] = new \DateTime('@' . $record['tstamp']);
			$records[$key]['author'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', $record['cruser']);
		}
		return $records;
	}

}


?>