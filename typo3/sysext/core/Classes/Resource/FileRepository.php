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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
	 * @param int $uid
	 * @return File
	 * @deprecated since TYPO3 6.2 CMS, will be removed 2 versions later
	 */
	public function findByUid($uid) {
		GeneralUtility::logDeprecatedFunction();
		return ResourceFactory::getInstance()->getFileObject($uid);
	}


	/**
	 * Internal function to retrieve the indexer service,
	 * if it does not exist, an instance will be created
	 *
	 * @return Service\IndexerService
	 */
	protected function getIndexerService() {
		if ($this->indexerService === NULL) {
			$this->indexerService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService');
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
	 * @param File $fileObject
	 * @return array The indexed file data
	 * @deprecated since TYPO3 6.2, will be removed two versions later - indexing should be handled transparently, not only upon request
	 */
	public function addToIndex(File $fileObject) {
		GeneralUtility::logDeprecatedFunction();
		return $this->getIndexerService()->indexFile($fileObject, FALSE);
	}

	/**
	 * Checks the index status of a file and returns FALSE if the file is not
	 * indexed, the uid otherwise.
	 *
	 * @param File $fileObject
	 * @return boolean|integer
	 * @deprecated since TYPO3 6.2, will be removed two versions later - use FileIndexRepository::isIndexed
	 */
	public function getFileIndexStatus(File $fileObject) {
		GeneralUtility::logDeprecatedFunction();
		$storageUid = $fileObject->getStorage()->getUid();
		$identifier = $fileObject->getIdentifier();
		$row = $this->getFileIndexRepository()->findOneByStorageUidAndIdentifier($storageUid, $identifier);
		return is_array($row) ? $row['uid'] : FALSE;
	}

	/**
	 * Returns an index record of a file, or FALSE if the file is not indexed.
	 *
	 * @param File $fileObject
	 * @return bool|array
	 * @deprecated since TYPO3 6.2, will be removed two versions later - use FileIndexRepository instead
	 */
	public function getFileIndexRecord(File $fileObject) {
		GeneralUtility::logDeprecatedFunction();
		return $this->getFileIndexRepository()->findOneByFileObject($fileObject);
	}

	/**
	 * Returns the index-data of all files within that folder
	 *
	 * @param Folder $folder
	 * @return array
	 * @deprecated since 6.2 - will be removed 2 versions later
	 */
	public function getFileIndexRecordsForFolder(Folder $folder) {
		GeneralUtility::logDeprecatedFunction();
		return $this->getFileIndexRepository()->findByFolder($folder);
	}

	/**
	 * Returns all files with the corresponding SHA-1 hash. This is queried
	 * against the database, so only indexed files will be found
	 *
	 * @param string $hash A SHA1 hash of a file
	 * @return array
	 * @deprecated since TYPO3 6.2, will be removed two versions later - use FileIndexRepository::findByContentHash
	 */
	public function findBySha1Hash($hash) {
		GeneralUtility::logDeprecatedFunction();
		$resultRows = $this->getFileIndexRepository()->findByContentHash($hash);

		$objects = array();
		foreach ($resultRows as $row) {
			$objects[] = $this->createDomainObject($row);
		}
		return $objects;
	}

	/**
	 * Find FileReference objects by relation to other records
	 *
	 * @param integer $tableName Table name of the related record
	 * @param integer $fieldName Field name of the related record
	 * @param integer $uid The UID of the related record (needs to be the localized uid, as translated IRRE elements relate to them)
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
				' AND uid_foreign=' . (int)$uid .
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
	 * @deprecated since TYPO3 6.2 LTS, will be removed two versions later - use FileIndexRepository::update
	 */
	public function update($modifiedObject) {
		GeneralUtility::logDeprecatedFunction();
		if ($modifiedObject instanceof File) {
			$this->getFileIndexRepository()->update($modifiedObject);
		}
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

	/**
	 * Return a file index repository
	 *
	 * @return FileIndexRepository
	 */
	protected function getFileIndexRepository() {
		return FileIndexRepository::getInstance();
	}

}
