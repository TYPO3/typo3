<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class FileRepository extends AbstractRepository {

	/**
	 * The main object type of this class. In some cases (fileReference) this
	 * repository can also return FileReference objects, implementing the
	 * common FileInterface.
	 *
	 * @var string
	 */
	protected $objectType = 'TYPO3\\CMS\\Core\\Resource\\File';

	/**
	 * Main File object storage table. Note that this repository also works on
	 * the sys_file_reference table when returning FileReference objects.
	 *
	 * @var string
	 */
	protected $table = 'sys_file';

	/**
	 * @var Service\IndexerService
	 */
	protected $indexerService = NULL;

	/**
	 * Internal function to retrieve the indexer service,
	 * if it does not exist, an instance will be created
	 *
	 * @return Service\IndexerService
	 */
	protected function getIndexerService() {
		if ($this->indexerService === NULL) {
			$this->indexerService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService');
		}
		return $this->indexerService;
	}

	/**
	 * Creates an object managed by this repository.
	 *
	 * @param array $databaseRow
	 * @return File
	 */
	protected function createDomainObject(array $databaseRow) {
		return $this->factory->getFileObject($databaseRow['uid'], $databaseRow);
	}

	/**
	 * Index a file object given as parameter
	 *
	 * @TODO : Check if the indexing functions really belong into the repository and shouldn't be part of an
	 * @TODO : indexing service, right now it's fine that way as this function will serve as the public API
	 * @param File $fileObject
	 * @return array The indexed file data
	 */
	public function addToIndex(File $fileObject) {
		return $this->getIndexerService()->indexFile($fileObject, FALSE);
	}

	/**
	 * Checks the index status of a file and returns FALSE if the file is not
	 * indexed, the uid otherwise.
	 *
	 * @TODO : Check if the indexing functions really belong into the repository and shouldn't be part of an
	 * @TODO : indexing service, right now it's fine that way as this function will serve as the public API
	 * @TODO : throw an exception if nothing found, for consistent handling as in AbstractRepository?
	 * @param File $fileObject
	 * @return bool|int
	 */
	public function getFileIndexStatus(File $fileObject) {
		$storageUid = $fileObject->getStorage()->getUid();
		$identifier = $fileObject->getIdentifier();
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid,storage,identifier',
			$this->table,
			sprintf('storage=%u AND identifier=%s', $storageUid, $GLOBALS['TYPO3_DB']->fullQuoteStr($identifier, $this->table))
		);
		if (!is_array($row)) {
			return FALSE;
		} else {
			return $row['uid'];
		}
	}

	/**
	 * Returns an index record of a file, or FALSE if the file is not indexed.
	 *
	 * @TODO : throw an exception if nothing found, for consistent handling as in AbstractRepository?
	 * @param File $fileObject
	 * @return bool|array
	 */
	public function getFileIndexRecord(File $fileObject) {
		$storageUid = $fileObject->getStorage()->getUid();
		$identifier = $fileObject->getIdentifier();
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			$this->table,
			sprintf('storage=%u AND identifier=%s', $storageUid, $GLOBALS['TYPO3_DB']->fullQuoteStr($identifier, $this->table))
		);
		if (!is_array($row)) {
			return FALSE;
		} else {
			return $row;
		}
	}

	/**
	 * Returns the index-data of all files within that folder
	 *
	 * @param Folder $folder
	 * @return array
	 */
	public function getFileIndexRecordsForFolder(Folder $folder) {
		$identifier = $folder->getIdentifier();
		$storage = $folder->getStorage()->getUid();
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->table,
			sprintf('storage=%u AND identifier LIKE %s AND NOT identifier LIKE %s',
					$storage,
					$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TYPO3_DB']->escapeStrForLike($identifier, $this->table) . '%', $this->table),
					$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TYPO3_DB']->escapeStrForLike($identifier, $this->table) . '%/%', $this->table)
			),
			'',
			'',
			'',
			'identifier'
		);
		return (array) $rows;
	}

	/**
	 * Returns all files with the corresponding SHA-1 hash. This is queried
	 * against the database, so only indexed files will be found
	 *
	 * @param string $hash A SHA1 hash of a file
	 * @return array
	 */
	public function findBySha1Hash($hash) {
		if (preg_match('/[^a-f0-9]*/i', $hash)) {

		}
		$resultRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->table,
			'sha1=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, $this->table)
		);
		$objects = array();
		foreach ($resultRows as $row) {
			$objects[] = $this->createDomainObject($row);
		}
		return $objects;
	}

	/**
	 * Find FileReference objects by relation to other records
	 *
	 * @param int $tableName Table name of the related record
	 * @param int $fieldName Field name of the related record
	 * @param int $uid The UID of the related record (needs to be the localized uid, as translated IRRE elements relate to them)
	 * @return array An array of objects, empty if no objects found
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function findByRelation($tableName, $fieldName, $uid) {
		$itemList = array();
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
			throw new \InvalidArgumentException('Uid of related record has to be an integer.', 1316789798);
		}
		$references = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_file_reference',
			'tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tableName, 'sys_file_reference') .
				' AND deleted = 0' .
				' AND hidden = 0' .
				' AND uid_foreign=' . intval($uid) .
				' AND fieldname=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($fieldName, 'sys_file_reference'),
			'',
			'sorting_foreign'
		);
		foreach ($references as $referenceRecord) {
			$itemList[] = $this->createFileReferenceObject($referenceRecord);
		}
		return $itemList;
	}

	/**
	 * Find FileReference objects by uid
	 *
	 * @param integer $uid The UID of the sys_file_reference record
	 * @return FileReference|boolean
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function findFileReferenceByUid($uid) {
		$fileReferenceObject = FALSE;
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
			throw new \InvalidArgumentException('uid of record has to be an integer.', 1316889798);
		}
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'sys_file_reference',
			'uid=' . $uid .
				' AND deleted=0' .
				' AND hidden=0'
		);
		if (is_array($row)) {
			$fileReferenceObject = $this->createFileReferenceObject($row);
		}
		return $fileReferenceObject;
	}

	/**
	 * Updates an existing file object in the database
	 *
	 * @param AbstractFile $modifiedObject
	 * @return void
	 */
	public function update($modifiedObject) {
		// TODO check if $modifiedObject is an instance of AbstractFile
		// TODO check if $modifiedObject is indexed
		$changedProperties = $modifiedObject->getUpdatedProperties();
		$properties = $modifiedObject->getProperties();
		$updateFields = array();
		foreach ($changedProperties as $propertyName) {
			$updateFields[$propertyName] = $properties[$propertyName];
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file', 'uid=' . $modifiedObject->getUid(), $updateFields);
	}

	/**
	 * Creates a FileReference object
	 *
	 * @param array $databaseRow
	 * @return FileReference
	 */
	protected function createFileReferenceObject(array $databaseRow) {
		return $this->factory->getFileReferenceObject($databaseRow['uid'], $databaseRow);
	}

}

?>