<?php
namespace TYPO3\CMS\Core\Resource;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
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
		$referenceUids = NULL;
		if ($this->getEnvironmentMode() === 'FE' && !empty($GLOBALS['TSFE']->sys_page)) {
			/** @var $frontendController \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
			$frontendController = $GLOBALS['TSFE'];
			$references = $this->getDatabaseConnection()->exec_SELECTgetRows(
				'uid',
				'sys_file_reference',
				'tablenames=' . $this->getDatabaseConnection()->fullQuoteStr($tableName, 'sys_file_reference') .
					' AND uid_foreign=' . (int)$uid .
					' AND fieldname=' . $this->getDatabaseConnection()->fullQuoteStr($fieldName, 'sys_file_reference')
					. $frontendController->sys_page->enableFields('sys_file_reference', $frontendController->showHiddenRecords),
				'',
				'sorting_foreign',
				'',
				'uid'
			);
			if (!empty($references)) {
				$referenceUids = array_keys($references);
			}
		} else {
			/** @var $relationHandler \TYPO3\CMS\Core\Database\RelationHandler */
			$relationHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
			$relationHandler->start(
				'', 'sys_file_reference', '', $uid, $tableName,
				\TYPO3\CMS\Backend\Utility\BackendUtility::getTcaFieldConfiguration($tableName, $fieldName)
			);
			if (!empty($relationHandler->tableArray['sys_file_reference'])) {
				$referenceUids = $relationHandler->tableArray['sys_file_reference'];
			}
		}
		if (!empty($referenceUids)) {
			foreach ($referenceUids as $referenceUid) {
				try {
					// Just passing the reference uid, the factory is doing workspace
					// overlays automatically depending on the current environment
					$itemList[] = $this->factory->getFileReferenceObject($referenceUid);
				} catch (ResourceDoesNotExistException $exception) {
					// No handling, just omit the invalid reference uid
				}
			}
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
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
			throw new \InvalidArgumentException('uid of record has to be an integer.', 1316889798);
		}
		try {
			$fileReferenceObject = $this->factory->getFileReferenceObject($uid);
		} catch (\InvalidArgumentException $exception) {
			$fileReferenceObject = FALSE;
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
	 * @deprecated Use $this->factory->getFileReferenceObject() directly
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
