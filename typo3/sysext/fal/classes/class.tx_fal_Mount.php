<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer Mount
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_Mount {

	/**
	 * The backend this mount uses
	 *
	 * @var tx_fal_storage_Interface
	 */
	protected $storageBackend;

	/**
	 * DESCRIPTION
	 *
	 * @var [to be defined]
	 */
	protected $basePath;

	/**
	 * DESCRIPTION
	 *
	 * @var [to be defined]
	 */
	protected $uid;

	/**
	 * DESCRIPTION
	 *
	 * @todo Andy Grunwald, 01.12.2010, why private? protected for access in xclasses?
	 *
	 * @var array<tx_fal_Mount>
	 */
	private static $instances = array();

	/**
	 * DESCRIPTION
	 *
	 * @param	[to be defined]		$mountInformation	DESCRIPTION
	 */
	public function __construct($mountInformation) {
		$this->uid = $mountInformation['uid'];
		$this->alias = $mountInformation['alias'];
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	integer			$mountUid	DESCRIPTION
	 * @return	[to be defined]				DESCRIPTION
	 *
	 * @throws RuntimeException
	 */
	public static function getInstanceForUid($mountUid) {
		if (is_null($mountUid)) {
			$mountUid = 0;
		}
		if (count(self::$instances) == 0) {
			self::loadMountsFromDatabase();
		}

		if (self::$instances[$mountUid]) {
			return self::$instances[$mountUid];
		}

		throw new RuntimeException("Mount with uid $mountUid was not found.");
	}

	/**
	 * Returns an instance of a file mount by its alias
	 *
	 * @static
	 * @param	string	$alias	DESCRIPTION
	 * @return	void
	 *
	 * @throws RuntimeException
	 */
	public static function getInstanceForAlias($alias) {
		if (count(self::$instances) == 0) {
			self::loadMountsFromDatabase();
		}

		foreach (self::$instances as $instance) {
			if ($instance->getAlias() == $alias) {
				return $instance;
			}
		}

		throw new RuntimeException("FAL mount $alias was not found.");
	}

	/**
	 * Loads all existing FAL mounts from the database and initializes them for later use.
	 *
	 * @static
	 * @return	void
	 */
	protected static function loadMountsFromDatabase() {
		$mountRecord = array('alias' => 'fileadmin', 'uid' => 0, 'storage_backend' => 'tx_fal_storage_FileSystemStorage');
		$storageConfiguration = array('relative' => TRUE, 'path' => 'fileadmin/');

		self::$instances[0] = self::mountObjectFactory($mountRecord, $storageConfiguration);

		$mountRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_files_mounts', 'deleted=0');
		foreach ($mountRecords as $mountRecord) {
			$storageConfiguration = t3lib_div::xml2array($mountRecord['backend_configuration']);
			$storageConfiguration = self::extractValuesFromFlexformArray($storageConfiguration['data']);

			self::$instances[$mountRecord['uid']] = self::mountObjectFactory($mountRecord, $storageConfiguration);
		}
	}

	/**
	 * Factory method for mount objects.
	 *
	 * @static
	 * @param	[to be defined]		$mountRecord			DESCRIPTION
	 * @param	[to be defined]		$storageConfiguration	DESCRIPTION
	 * @return	[to be defined]								DESCRIPTION
	 *
	 * @throws RuntimeException
	 */
	protected static function mountObjectFactory($mountRecord, $storageConfiguration) {
		$storageBackendClass = $mountRecord['storage_backend'];
		if (!class_exists($storageBackendClass)) {
			throw new RuntimeException("Class $storageBackendClass does not exist.");
		}
		$storageBackend = t3lib_div::makeInstance($storageBackendClass, $storageConfiguration);

		$mountObject = new tx_fal_Mount($mountRecord);
		$mountObject->setStorageBackend($storageBackend);

		return $mountObject;
	}

	/**
	 * Helper function to ease unit testing. Ignore in normal operation.
	 *
	 * @static
	 * @param	array	$instances	DESCRIPTION
	 * @return	void
	 */
	public static function _setInstances(array $instances) {
		self::$instances = $instances;
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	array	$flexformArray	DESCRIPTION
	 * @return	[to be defined]			DESCRIPTION
	 */
	protected static function extractValuesFromFlexformArray(array $flexformArray) {
		foreach ($flexformArray as $sheet) {
			foreach ($sheet as $language) {
				foreach ($language as $fieldName => $fieldValue) {
					$values[$fieldName] = $fieldValue['vDEF'];
				}
			}
		}

		return $values;
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	tx_fal_storage_Interface	$storageBackend		The backend to use
	 */
	public function setStorageBackend(tx_fal_storage_Interface $storageBackend) {
		$this->storageBackend = $storageBackend;
	}

	/**
	 * Returns the storage backend used by this file mount.
	 *
	 * @return	tx_fal_storage_Interface
	 */
	public function getStorageBackend() {
		return $this->storageBackend;
	}

	/**
	 * Creates a directory inside this mount. Success of this operation depends - among other things - on the directory
	 * support by the backend.
	 *
	 * @param	[to be defined]		$path			The path to create the directory in
	 * @param	[to be defined]		$directoryName	The directory to create
	 * @return	boolean								TRUE if the directory could be created
	 */
	public function createDirectory($path, $directoryName) {
		return $this->storageBackend->createDirectory($path, $directoryName);
	}

	/**
	 * DESCRIPTION
	 *
	 * @todo Implement tx_fal_Mount::getDirectoryListing
	 *
	 * @param	[to be defined]		$path		DESCRIPTION
	 * @return	[to be defined]					DESCRIPTION
	 */
	public function getDirectoryListing($path) {
	}

	/**
	 * Getter for mount uid
	 *
	 * @return	integer		DESCRIPTION
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * DESCRIPTION
	 *
	 * @return	[to be defined]
	 */
	public function getAlias() {
		return $this->alias;
	}

	/**
	 * DESCRIPTION
	 *
	 * @return	[to be defined]
	 */
	public function getBasePath() {
		return $this->basePath;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Mount.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Mount.php']);
}
?>