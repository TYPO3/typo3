<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Benjamin Mack <benni@typo3.org>
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
 * Repository for accessing files
 * it also serves as the public API for the indexing part of files in general
 *
 * @author Benjamin Mack <benni@typo3.org>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 * @package 	TYPO3
 * @subpackage 	t3lib
 */
class ProcessedFileRepository extends \TYPO3\CMS\Core\Resource\AbstractRepository {

	/**
	 * The main object type of this class. In some cases (fileReference) this
	 * repository can also return FileReference objects, implementing the
	 * common FileInterface.
	 *
	 * @var string
	 */
	protected $objectType = 'TYPO3\\CMS\\Core\\Resource\\ProcessedFile';

	/**
	 * Main File object storage table. Note that this repository also works on
	 * the sys_file_reference table when returning FileReference objects.
	 *
	 * @var string
	 */
	protected $table = 'sys_file_processedfile';

	/**
	 * Creates an object managed by this repository.
	 *
	 * @param array $databaseRow
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	protected function createDomainObject(array $databaseRow) {
		return $this->factory->getFileObject($databaseRow['uid'], $databaseRow);
	}

	/**
	 * Loads index-data into processedFileObject
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFileObject
	 * @return boolean
	 */
	public function populateDataOfProcessedFileObject(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFileObject) {
		/** @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
		$recordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->table, 'original=' . intval($processedFileObject->getOriginalFile()->getUid()) . ' AND checksum=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($processedFileObject->calculateChecksum(), $this->table) . ' AND deleted=0');
		// Update the properties if the data was found
		if (is_array($recordData)) {
			$processedFileObject->updateProperties($recordData);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Adds a processedfile object in the database
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @return void
	 */
	public function add($processedFile) {
		$insertFields = $processedFile->toArray();
		$insertFields['crdate'] = ($insertFields['tstamp'] = time());
		// @todo: make sure that the toArray method only
		// contains fields that actually *exist* in the table
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->table, $insertFields);
	}

	/**
	 * Updates an existing file object in the database
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @return void
	 */
	public function update($processedFile) {
		$uid = intval($processedFile->getProperty('uid'));
		if ($uid > 0) {
			// @todo: make sure that the toArray method only
			// contains fields that actually *exist* in the table
			$updateFields = $processedFile->toArray();
			$updateFields['tstamp'] = time();
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table, 'uid=' . $uid, $updateFields);
		}
	}

}


?>