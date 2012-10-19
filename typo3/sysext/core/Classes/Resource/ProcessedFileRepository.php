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
	 * @var \TYPO3\CMS\Core\Resource\Processing\ProcessingFactory
	 */
	protected $factory;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Processing\\ProcessingFactory');
	}

	/**
	 * Creates an object managed by this repository.
	 *
	 * @param array $databaseRow
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	protected function createDomainObject(array $databaseRow) {
		return $this->factory->createProcessedFileObjectFromDatabase($databaseRow);
	}

	/**
	 * Adds a processedfile object in the database
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @return void
	 */
	public function add($processedFile) {
		if ($processedFile->isPersisted()) {
			$this->update($processedFile);
		} else {
			$insertFields = $processedFile->toArray();
			$insertFields['crdate'] = $insertFields['tstamp'] = time();
			$insertFields = $this->cleanUnavailableColumns($insertFields);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->table, $insertFields);
			$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
			$processedFile->updateProperties(array('uid' => $uid));
		}
	}

	/**
	 * Updates an existing file object in the database
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @return void
	 */
	public function update($processedFile) {
		if ($processedFile->isPersisted()) {
			$uid = intval($processedFile->getProperty('uid'));
			$updateFields = $this->cleanUnavailableColumns($processedFile->toArray());
			$updateFields['tstamp'] = time();
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table, 'uid=' . $uid, $updateFields);
		}
	}

	/**
	 * @param File $file
	 * @param string $task The task that should be executed on the file
	 * @param array $configuration
	 *
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function findOneByOriginalFileAndTaskAndConfiguration(File $file, $task, array $configuration) {
		/** @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			$this->table,
			'original = ' . $file->getUid() .
				' AND task = \'' . $GLOBALS['TYPO3_DB']->escapeStrForLike($task, $this->table) . '\'' .
				' AND configuration = \'' . serialize($configuration) . '\''
		);

		if (is_array($row)) {
			$processedFile = $this->createDomainObject($row);
		} else {
			$processedFile = $this->factory->createNewProcessedFileObject($file, $task, $configuration);
		}
		return $processedFile;
	}

	/**
	 * Removes all array keys which cannot be persisted
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function cleanUnavailableColumns(array $data) {
		$possibleFields = array_keys($GLOBALS['TYPO3_DB']->admin_get_fields($this->table));

		$fieldsToBeRemoved = array_diff(array_keys($data), $possibleFields);
		foreach ($fieldsToBeRemoved as $columnName) {
			unset($data[$columnName]);
		}

		return $data;
	}
}


?>