<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Andreas Wolf <andreas.wolf@typo3.org>
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

use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class FileIdentifierHashUpdate adds IdentifierHashes
 */
class FileIdentifierHashUpdate extends AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Add the file identifier hash to existing sys_file records and update the settings for local storages';

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $sqlQueries = array();

	/**
	 * @var ResourceStorage[]
	 */
	protected $storages;

	/**
	 * @var \TYPO3\CMS\Core\Resource\StorageRepository
	 */
	protected $storageRepository;

	/**
	 * Creates this object
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Initialize the storage repository.
	 */
	public function init() {
		$this->storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$this->storages = $this->storageRepository->findAll();
		// Add default storage for core files
		$this->storages[] = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getStorageObject(0);
	}

	/**
	 * Checks if an update is needed.
	 *
	 * @param string &$description The description for the update
	 * @return boolean TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Add file identifier hash to sys_file records, where it is missing. Additionally upgrade storage configurations.';
		$unhashedFileCount = $this->db->exec_SELECTcountRows(
			'uid',
			'sys_file',
			'identifier_hash = "" OR folder_hash = ""'
		);

		$unmigratedStorageCount = $this->db->exec_SELECTcountRows(
			'uid',
			'sys_file_storage',
			'driver = "Local" AND configuration NOT LIKE "%caseSensitive%"'
		);

		return $unhashedFileCount > 0 || $unmigratedStorageCount > 0;
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
		foreach ($this->storages as $storage) {
			$dbQueries = array_merge($dbQueries, $this->updateIdentifierHashesForStorage($storage));
		}

		$dbQueries = array_merge($dbQueries, $this->migrateStorages());

		$this->markWizardAsDone();
		return TRUE;
	}

	/**
	 * @return array
	 */
	protected function migrateStorages() {
		$dbQueries = array();
		$unmigratedStorages = $this->db->exec_SELECTgetRows(
			'uid, configuration',
			'sys_file_storage',
			'driver = "Local" AND configuration NOT LIKE "%caseSensitive%"'
		);

		/** @var $flexObj \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
		$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');


		foreach ($unmigratedStorages as $storage) {
			$flexFormXml = $storage['configuration'];
			$configurationArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($flexFormXml);

			$caseSensitive = $this->testCaseSensitivity(
				$configurationArray['data']['sDEF']['lDEF']['pathType']['vDEF'] == 'relative' ?
					PATH_site . $configurationArray['data']['sDEF']['lDEF']['basePath']['vDEF'] :
					$configurationArray['data']['sDEF']['lDEF']['basePath']['vDEF']
			);
			$configurationArray['data']['sDEF']['lDEF']['caseSensitive'] = array('vDEF' => $caseSensitive);

			$configuration = $flexObj->flexArray2Xml($configurationArray);
			$dbQueries[] = $query = $this->db->UPDATEquery(
				'sys_file_storage',
				'uid=' . $storage['uid'],
				array(
					'configuration' => $configuration
				)
			);
			$this->db->sql_query($query);
		}
		return $dbQueries;
	}

	/**
	 * Creates file identifier hashes for a single storage.
	 *
	 * @param ResourceStorage $storage The storage to update
	 * @return array The executed database queries
	 */
	protected function updateIdentifierHashesForStorage(ResourceStorage $storage) {
		$queries = array();

		if (!ExtensionManagementUtility::isLoaded('dbal')) {
			// if DBAL is not loaded, we're using MySQL and can thus use their
			// SHA1() function
			if ($storage->usesCaseSensitiveIdentifiers()) {
				$updateCall = 'SHA1(identifier)';
			} else {
				$updateCall = 'SHA1(LOWER(identifier))';
			}
			$queries[] = $query = sprintf(
				'UPDATE sys_file SET identifier_hash = %s WHERE storage=%d',
				$updateCall,
				$storage->getUid()
			);
			$this->db->sql_query($query);

			// folder hashes cannot be done with one call: so do it manually
			$files = $this->db->exec_SELECTgetRows('uid, storage, identifier', 'sys_file',
				sprintf('storage=%d AND folder_hash=""', $storage->getUid())
			);

			foreach ($files as $file) {
				$folderHash = $storage->hashFileIdentifier($storage->getFolderIdentifierFromFileIdentifier($file['identifier']));

				$queries[] = $query = $this->db->UPDATEquery(
					'sys_file',
					'uid=' . $file['uid'],
					array(
						'folder_hash' => $folderHash
					)
				);

				$this->db->sql_query($query);
			}
		} else {
			// manually hash the identifiers when using DBAL
			$files = $this->db->exec_SELECTgetRows('uid, storage, identifier', 'sys_file',
				sprintf('storage=%d AND identifier_hash=""', $storage->getUid())
			);

			foreach ($files as $file) {
				$hash = $storage->hashFileIdentifier($file['identifier']);
				$folderHash = $storage->hashFileIdentifier($storage->getFolderIdentifierFromFileIdentifier($file['identifier']));

				$queries[] = $query = $this->db->UPDATEquery(
					'sys_file',
					'uid=' . $file['uid'],
					array(
						'identifier_hash' => $hash,
						'folder_hash' => $folderHash
					)
				);

				$this->db->sql_query($query);
			}
		}

		return $queries;
	}


	/**
	 * Test if the local filesystem is case sensitive
	 *
	 * @param $absolutePath
	 * @return boolean
	 */
	protected function testCaseSensitivity($absolutePath) {
		$caseSensitive = TRUE;
		$path = rtrim($absolutePath, '/') . '/aAbB';
		$testFileExists = file_exists($path);

		// create test file
		if (!$testFileExists) {
			@touch($path);
		}

		// do the actual sensitivity check
		if (file_exists(strtoupper($path)) && file_exists(strtolower($path))) {
			$caseSensitive = FALSE;
		}

		// clean filesystem
		if (!$testFileExists) {
			@unlink($path);
		}

		return $caseSensitive;
	}
}
