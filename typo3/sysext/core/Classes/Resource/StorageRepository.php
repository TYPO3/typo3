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
 * Repository for accessing the file mounts
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class StorageRepository extends AbstractRepository {

	/**
	 * @var string
	 */
	protected $objectType = 'TYPO3\\CMS\\Core\\Resource\\ResourceStorage';

	/**
	 * @var string
	 */
	protected $table = 'sys_file_storage';

	/**
	 * @var string
	 */
	protected $typeField = 'type';

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger
	 */
	protected $logger;

	public function __construct() {
		parent::__construct();

		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager');
		$this->logger = $logManager->getLogger(__CLASS__);
	}

	/**
	 * Finds storages by type.
	 *
	 * @param string $storageType
	 * @return ResourceStorage[]
	 */
	public function findByStorageType($storageType) {
		/** @var $driverRegistry Driver\DriverRegistry */
		$driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Driver\DriverRegistry');
		$storageObjects = array();
		$whereClause = $this->typeField . ' = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($storageType, $this->table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->table,
			$whereClause . $this->getWhereClauseForEnabledFields()
		);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($driverRegistry->driverExists($row['driver'])) {
				$storageObjects[] = $this->createDomainObject($row);
			} else {
				$this->logger->warning(
					sprintf('Could not instantiate storage "%s" because of missing driver.', array($row['name'])),
					$row
				);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $storageObjects;
	}

	/**
	 * Returns a list of mountpoints that are available in the VFS.
	 * In case no storage exists this automatically created a storage for fileadmin/
	 *
	 * @return ResourceStorage[]
	 */
	public function findAll() {
			// check if we have never created a storage before (no records, regardless of the enableFields),
			// only fetch one record for that (is enough). If no record is found, create the fileadmin/ storage
		$storageObjectsCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $this->table, '1=1');
		if ($storageObjectsCount === 0) {
			$this->createLocalStorage(
				'fileadmin/ (auto-created)',
				$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],
				'relative',
				'This is the local fileadmin/ directory. This storage mount has been created automatically by TYPO3.'
			);
		}

		$storageObjects = array();
		$whereClause = NULL;
		if ($this->type != '') {
			$whereClause = $this->typeField . ' = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->type, $this->table);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->table,
			($whereClause ? $whereClause : '1=1') . $this->getWhereClauseForEnabledFields()
		);

		/** @var $driverRegistry Driver\DriverRegistry */
		$driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Driver\DriverRegistry');

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($driverRegistry->driverExists($row['driver'])) {
				$storageObjects[] = $this->createDomainObject($row);
			} else {
				$this->logger->warning(
					sprintf('Could not instantiate storage "%s" because of missing driver.', array($row['name'])),
					$row
				);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $storageObjects;
	}

	/**
	 * Create the initial local storage base e.g. for the fileadmin/ directory.
	 *
	 * @param string $name
	 * @param string $basePath
	 * @param string $pathType
	 * @param string $description
	 * @return integer uid of the inserted record
	 */
	public function createLocalStorage($name, $basePath, $pathType, $description = '') {

			// create the FlexForm for the driver configuration
		$flexFormData = array(
			'data' => array(
				'sDEF' => array(
					'lDEF' => array(
						'basePath' => array('vDEF' => rtrim($basePath, '/') . '/'),
						'pathType' => array('vDEF' => $pathType)
					)
				)
			)
		);

		/** @var $flexObj \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
		$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
		$flexFormXml = $flexObj->flexArray2Xml($flexFormData, TRUE);

			// create the record
		$field_values = array(
			'pid' => 0,
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'crdate' => $GLOBALS['EXEC_TIME'],
			'name' => $name,
			'description' => $description,
			'driver' => 'Local',
			'configuration' => $flexFormXml,
			'is_online' => 1,
			'is_browsable' => 1,
			'is_public' => 1,
			'is_writable' => 1
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_storage', $field_values);
		return (int) $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Creates an object managed by this repository.
	 *
	 * @param array $databaseRow
	 * @return ResourceStorage
	 */
	protected function createDomainObject(array $databaseRow) {
		return $this->factory->getStorageObject($databaseRow['uid'], $databaseRow);
	}

	/**
	 * get the WHERE clause for the enabled fields of this TCA table
	 * depending on the context
	 *
	 * @return string the additional where clause, something like " AND deleted=0 AND hidden=0"
	 */
	protected function getWhereClauseForEnabledFields() {
		if (is_object($GLOBALS['TSFE'])) {
			// frontend context
			$whereClause = $GLOBALS['TSFE']->sys_page->enableFields($this->table);
			$whereClause .= $GLOBALS['TSFE']->sys_page->deleteClause($this->table);
		} else {
			// backend context
			$whereClause = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($this->table);
			$whereClause .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($this->table);
		}
		return $whereClause;
	}
}


?>