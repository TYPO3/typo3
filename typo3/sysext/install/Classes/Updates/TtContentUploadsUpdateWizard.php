<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 * Upgrade wizard which goes through all files referenced in the tt_content.media filed
 * and creates sys_file records as well as sys_file_reference records for the individual usages.
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 */
class TtContentUploadsUpdateWizard extends AbstractUpdate {

	const FOLDER_ContentUploads = '_migrated/content_uploads';

	/**
	 * @var string
	 */
	protected $title = 'Migrate file relations of tt_content "uploads"';

	/**
	 * @var string
	 */
	protected $targetDirectory;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected $fileFactory;

	/**
	 * @var \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
	 */
	protected $fileIndexRepository;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * Initialize all required repository and factory objects.
	 *
	 * @throws \RuntimeException
	 */
	protected function init() {
		$fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';
		/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
		$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storages = $storageRepository->findAll();
		foreach ($storages as $storage) {
			$storageRecord = $storage->getStorageRecord();
			$configuration = $storage->getConfiguration();
			$isLocalDriver = $storageRecord['driver'] === 'Local';
			$isOnFileadmin = !empty($configuration['basePath']) && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($configuration['basePath'], $fileadminDirectory);
			if ($isLocalDriver && $isOnFileadmin) {
				$this->storage = $storage;
				break;
			}
		}
		if (!isset($this->storage)) {
			throw new \RuntimeException('Local default storage could not be initialized - might be due to missing sys_file* tables.');
		}
		$this->fileFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$this->fileIndexRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
		$this->targetDirectory = PATH_site . $fileadminDirectory . self::FOLDER_ContentUploads . '/';
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description The description for the update
	 * @return boolean TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$updateNeeded = FALSE;
		// Fetch records where the field media does not contain a plain integer value
		// * check whether media field is not empty
		// * then check whether media field does not contain a reference count (= not integer)
		$mapping = $this->getTableColumnMapping();
		$sql = $GLOBALS['TYPO3_DB']->SELECTquery(
			'COUNT(' . $mapping['mapFieldNames']['uid'] . ')',
			$mapping['mapTableName'],
			'1=1'
		);
		$whereClause = $this->getDbalCompliantUpdateWhereClause();
		$sql = str_replace('WHERE 1=1', $whereClause, $sql);
		$resultSet = $GLOBALS['TYPO3_DB']->sql_query($sql);
		$notMigratedRowsCount = 0;
		if ($resultSet !== FALSE) {
			list($notMigratedRowsCount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($resultSet);
			$notMigratedRowsCount = (int)$notMigratedRowsCount;
			$GLOBALS['TYPO3_DB']->sql_free_result($resultSet);
		}
		if ($notMigratedRowsCount > 0) {
			$description = 'There are Content Elements of type "upload" which are referencing files that are not using ' . ' the File Abstraction Layer. This wizard will move the files to fileadmin/' . self::FOLDER_ContentUploads . ' and index them.';
			$updateNeeded = TRUE;
		}
		return $updateNeeded;
	}

	/**
	 * Performs the database update.
	 *
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$this->init();
		$records = $this->getRecordsFromTable('tt_content');
		$this->checkPrerequisites();
		foreach ($records as $singleRecord) {
			$this->migrateRecord($singleRecord);
		}
		return TRUE;
	}

	/**
	 * Ensures a new folder "fileadmin/content_upload/" is available.
	 *
	 * @return void
	 */
	protected function checkPrerequisites() {
		if (!$this->storage->hasFolder(self::FOLDER_ContentUploads)) {
			$this->storage->createFolder(self::FOLDER_ContentUploads, $this->storage->getRootLevelFolder());
		}
	}

	/**
	 * Processes the actual transformation from CSV to sys_file_references
	 *
	 * @param array $record
	 * @return void
	 */
	protected function migrateRecord(array $record) {
		$collections = array();
		if (trim($record['select_key'])) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_collection', array(
				'pid' => $record['pid'],
				'title' => $record['select_key'],
				'storage' => $this->storage->getUid(),
				'folder' => ltrim('fileadmin/', $record['select_key'])
			));
			$collections[] = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}
		$files = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $record['media'], TRUE);
		$descriptions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('
', $record['imagecaption']);
		$titleText = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('
', $record['titleText']);
		$i = 0;
		foreach ($files as $file) {
			if (file_exists(PATH_site . 'uploads/media/' . $file)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move(PATH_site . 'uploads/media/' . $file, $this->targetDirectory . $file);
				$fileObject = $this->storage->getFile(self::FOLDER_ContentUploads . '/' . $file);
				$this->fileIndexRepository->add($fileObject);
				$dataArray = array(
					'uid_local' => $fileObject->getUid(),
					'tablenames' => 'tt_content',
					'uid_foreign' => $record['uid'],
					// the sys_file_reference record should always placed on the same page
					// as the record to link to, see issue #46497
					'pid' => $record['pid'],
					'fieldname' => 'media',
					'sorting_foreign' => $i
				);
				if (isset($descriptions[$i])) {
					$dataArray['description'] = $descriptions[$i];
				}
				if (isset($titleText[$i])) {
					$dataArray['alternative'] = $titleText[$i];
				}
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_reference', $dataArray);
				unlink(PATH_site . 'uploads/media/' . $file);
			}
			$i++;
		}
		$this->cleanRecord($record, $i, $collections);
	}

	/**
	 * Removes the old fields from the database-record
	 *
	 * @param array $record
	 * @param integer $fileCount
	 * @param array $collectionUids
	 * @return void
	 */
	protected function cleanRecord(array $record, $fileCount, array $collectionUids) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'uid = ' . $record['uid'], array(
			'media' => $fileCount,
			'imagecaption' => '',
			'titleText' => '',
			'altText' => '',
			'select_key' => '',
			'file_collections' => implode(',', $collectionUids)
		));
	}

	/**
	 * Retrieve every record which needs to be processed
	 *
	 * @return array
	 */
	protected function getRecordsFromTable() {
		$mapping = $this->getTableColumnMapping();
		$reverseFieldMapping = array_flip($mapping['mapFieldNames']);

		$fields = array();
		foreach (array('uid', 'pid', 'select_key', 'media', 'imagecaption', 'titleText') as $columnName) {
			$fields[] = $mapping['mapFieldNames'][$columnName];
		}
		$fields = implode(',', $fields);

		$sql = $GLOBALS['TYPO3_DB']->SELECTquery(
			$fields,
			$mapping['mapTableName'],
			'1=1'
		);
		$whereClause = $this->getDbalCompliantUpdateWhereClause();
		$sql = str_replace('WHERE 1=1', $whereClause, $sql);
		$resultSet = $GLOBALS['TYPO3_DB']->sql_query($sql);
		$records = array();
		if (!$GLOBALS['TYPO3_DB']->sql_error()) {
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultSet)) !== FALSE) {
				// Mapping back column names to native TYPO3 names
				$record = array();
				foreach ($reverseFieldMapping as $columnName => $finalColumnName) {
					$record[$finalColumnName] = $row[$columnName];
				}
				$records[] = $record;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($resultSet);
		}
		return $records;
	}

	/**
	 * Returns a DBAL-compliant where clause to be used for the update where clause.
	 * We have DBAL-related code here because the SQL parser is not able to properly
	 * parse this complex condition but we know that it is compatible with the DBMS
	 * we support in TYPO3 Core.
	 *
	 * @return string
	 */
	protected function getDbalCompliantUpdateWhereClause() {
		$mapping = $this->getTableColumnMapping();
		$this->quoteIdentifiers($mapping);

		$where = sprintf(
			'WHERE %s <> \'\' AND CAST(CAST(%s AS DECIMAL) AS CHAR) <> %s OR (%s = \'uploads\' AND %s != \'\')',
			$mapping['mapFieldNames']['media'],
			$mapping['mapFieldNames']['media'],
			$mapping['mapFieldNames']['media'],
			$mapping['mapFieldNames']['CType'],
			$mapping['mapFieldNames']['select_key']
		);

		return $where;
	}

	/**
	 * Returns the table and column mapping.
	 *
	 * @return array
	 */
	protected function getTableColumnMapping() {
		$mapping = array(
			'mapTableName' => 'tt_content',
			'mapFieldNames' => array(
				'uid'          => 'uid',
				'pid'          => 'pid',
				'media'        => 'media',
				'imagecaption' => 'imagecaption',
				'titleText'    => 'titleText',
				'CType'        => 'CType',
				'select_key'   => 'select_key',
			)
		);

		if ($GLOBALS['TYPO3_DB'] instanceof \TYPO3\CMS\Dbal\Database\DatabaseConnection) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping']['tt_content'])) {
				$mapping = array_merge_recursive($mapping, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping']['tt_content']);
			}
		}

		return $mapping;
	}

	/**
	 * Quotes identifiers for DBAL-compliant query.
	 *
	 * @param array &$mapping
	 * @return void
	 */
	protected function quoteIdentifiers(array &$mapping) {
		if ($GLOBALS['TYPO3_DB'] instanceof \TYPO3\CMS\Dbal\Database\DatabaseConnection) {
			if (!$GLOBALS['TYPO3_DB']->runningNative() && !$GLOBALS['TYPO3_DB']->runningADOdbDriver('mysql')) {
				$mapping['mapTableName'] = '"' . $mapping['mapTableName'] . '"';
				foreach ($mapping['mapFieldNames'] as $key => &$value) {
					$value = '"' . $value . '"';
				}
			}
		}
	}

}
