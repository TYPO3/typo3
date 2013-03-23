<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Benni Mack <benni@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Performs certain DB updates in order to ensure that the DB fields
 * are set properly. Currently this is used for ensuring that there
 * are no sys_file_reference records with PID=0 where the connected
 * parent records (e.g. a tt_content record) are not on PID=0
 *
 * @author Benni Mack <benni@typo3.org>
 * @license http://www.gnu.org/copyleft/gpl.html
 */
class ReferenceIntegrityUpdateWizard extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Ensures the database integrity for File Abstraction records';

	/**
	 * Checks if an update is needed
	 *
	 * @param 	string		&$description: The description for the update
	 * @return 	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Checks if there are file references that are on the root level. This could have happened due to a misconfigured previous migration.';
		return count($this->getRequiredUpdates()) > 0;
	}

	/**
	 * Performs the database update.
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$updates = $this->getRequiredUpdates();
		if (isset($updates['inproperConnectedFileReferences'])) {
			foreach ($updates['inproperConnectedFileReferences'] as $fileReferenceRecord) {
				if ($fileReferenceRecord['newpid'] > 0) {
					$updateQuery = $GLOBALS['TYPO3_DB']->UPDATEquery(
						'sys_file_reference',
						'uid=' . intval($fileReferenceRecord['uid']),
						array('pid' => $fileReferenceRecord['newpid'])
					);
					$GLOBALS['TYPO3_DB']->sql_query($updateQuery);
					$dbQueries[] = $updateQuery;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Determine all DB updates that need to be done
	 *
	 * @return array
	 */
	protected function getRequiredUpdates() {
		$requiredUpdates = array();
		$inproperConnectedFileReferences = $this->getInproperConnectedFileReferences();
		if (count($inproperConnectedFileReferences) > 0) {
			$requiredUpdates['inproperConnectedFileReferences'] = $inproperConnectedFileReferences;
		}
		return $requiredUpdates;
	}

	/**
	 * fetches a list of all sys_file_references that have PID=0
	 *
	 * @return mixed
	 */
	protected function getFileReferencesOnRootlevel() {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, pid, uid_local AS fileuid, uid_foreign AS targetuid, tablenames AS targettable',
			'sys_file_reference',
			'pid=0 AND deleted=0'
		);
	}

	/**
	 * fetches all sys_file_reference records that are on PID=0 BUT their counter parts (the target record)
	 * is NOT on pid=0
	 *
	 * @return array
	 */
	protected function getInproperConnectedFileReferences() {
		$inproperConnectedReferences = array();
		// fetch all references on root level
		$sysFileReferences = $this->getFileReferencesOnRootlevel();
		foreach ($sysFileReferences as $fileReferenceRecord) {
			// if the target table is pages (e.g. when adding a file reference to the pages->media
			// record, then the
			$whereClause = 'uid=' . intval($fileReferenceRecord['targetuid']);
			if ($fileReferenceRecord['targettable'] === 'pages') {
				$isPageReference = TRUE;
			} else {
				$isPageReference = FALSE;
				$whereClause .= ' AND pid<>0';
			}
			// check the target table, if the target record is NOT on the rootlevel
			$targetRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'uid, pid',
				$fileReferenceRecord['targettable'],
				$whereClause
			);
			// only add the file reference if the target record is not on PID=0
			if ($targetRecord !== NULL) {
				$fileReferenceRecord['newpid'] = ($isPageReference ? $targetRecord['uid'] : $targetRecord['pid']);
				$inproperConnectedReferences[] = $fileReferenceRecord;
			}
		}
		return $inproperConnectedReferences;
	}
}


?>