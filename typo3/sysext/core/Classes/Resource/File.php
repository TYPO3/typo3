<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Ingo Renner <ingo@typo3.org>
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
 *  (c) 2013-20xx Steffen Ritter <steffen.ritter@typo3.org>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File representation in the file abstraction layer.
 *
 */
class File extends AbstractFile {

	/**
	 * @var bool
	 */
	protected $metaDataLoaded = FALSE;

	/**
	 * @var array
	 */
	protected $metaDataProperties = array();

	/**
	 * Set to TRUE while this file is being indexed - used to prevent some endless loops
	 *
	 * @var boolean
	 */
	protected $indexingInProgress = FALSE;

	/**
	 * Contains the names of all properties that have been update since the
	 * instantiation of this object
	 *
	 * @var array
	 */
	protected $updatedProperties = array();

	/**
	 * @var \TYPO3\CMS\Core\Resource\Service\IndexerService
	 */
	protected $indexerService = NULL;

	/**
	 * Constructor for a file object. Should normally not be used directly, use
	 * the corresponding factory methods instead.
	 *
	 * @param array $fileData
	 * @param ResourceStorage $storage
	 * @param array $metaData
	 */
	public function __construct(array $fileData, ResourceStorage $storage, $metaData = array()) {
		$this->identifier = $fileData['identifier'];
		$this->name = $fileData['name'];
		$this->properties = $fileData;
		$this->storage = $storage;
		if ($metaData !== array()) {
			$this->metaDataLoaded = TRUE;
			$this->metaDataProperties = $metaData;
		}
	}

	/*******************************
	 * VARIOUS FILE PROPERTY GETTERS
	 *******************************/
	/**
	 * Returns a property value
	 *
	 * @param string $key
	 * @return mixed Property value
	 */
	public function getProperty($key) {
		if (parent::hasProperty($key)) {
			return parent::getProperty($key);
		} else {
			if (!$this->metaDataLoaded) {
				$this->loadMetaData();
			}
			return array_key_exists($key, $this->metaDataProperties) ? $this->metaDataProperties[$key] : NULL;
		}
	}

	/**
	 * Checks if the file has a (metadata) property which
	 * can be retrieved by "getProperty"
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasProperty($key) {
		if (!parent::hasProperty($key)) {
			if (!$this->metaDataLoaded) {
				$this->loadMetaData();
			}
			return array_key_exists($key, $this->metaDataProperties);
		}
		return TRUE;
	}


	/**
	 * Returns the properties of this object.
	 *
	 * @return array
	 */
	public function getProperties() {
		if (!$this->metaDataLoaded) {
			$this->loadMetaData();
		}
		return array_merge(parent::getProperties(), array_diff_key((array)$this->metaDataProperties, parent::getProperties()));
	}

	/**
	 * Returns the MetaData
	 *
	 * @return array|null
	 * @internal
	 */
	public function _getMetaData() {
		if (!$this->metaDataLoaded) {
			$this->loadMetaData();
		}
		return $this->metaDataProperties;
	}

	/******************
	 * CONTENTS RELATED
	 ******************/
	/**
	 * Get the contents of this file
	 *
	 * @return string File contents
	 */
	public function getContents() {
		return $this->getStorage()->getFileContents($this);
	}

	/**
	 * Gets SHA1 hash.
	 *
	 * @return string
	 */
	public function getSha1() {
		if (empty($this->properties['sha1'])) {
			$this->properties['sha1'] = parent::getSha1();
		}
		return $this->properties['sha1'];
	}

	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return File The file object (allows chaining).
	 */
	public function setContents($contents) {
		$this->getStorage()->setFileContents($this, $contents);
		return $this;
	}

	/***********************
	 * INDEX RELATED METHODS
	 ***********************/
	/**
	 * Returns TRUE if this file is indexed
	 *
	 * @return boolean|NULL
	 */
	public function isIndexed() {
		return TRUE;
	}

	/**
	 * Loads MetaData from Repository
	 * @return void
	 */
	protected function loadMetaData() {
		if (!$this->indexingInProgress) {
			$this->indexingInProgress = TRUE;
			$this->metaDataProperties = $this->getMetaDataRepository()->findByFile($this);
			$this->metaDataLoaded = TRUE;
			$this->indexingInProgress = FALSE;
		}

	}

	/**
	 * Updates the properties of this file, e.g. after re-indexing or moving it.
	 * By default, only properties that exist as a key in the $properties array
	 * are overwritten. If you want to explicitly unset a property, set the
	 * corresponding key to NULL in the array.
	 *
	 * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
	 *
	 * @param array $properties
	 * @return void
	 * @internal
	 */
	public function updateProperties(array $properties) {
		// Setting identifier and name to update values; we have to do this
		// here because we might need a new identifier when loading
		// (and thus possibly indexing) a file.
		if (isset($properties['identifier'])) {
			$this->identifier = $properties['identifier'];
		}
		if (isset($properties['name'])) {
			$this->name = $properties['name'];
		}

		if ($this->properties['uid'] != 0 && isset($properties['uid'])) {
			unset($properties['uid']);
		}
		foreach ($properties as $key => $value) {
			if ($this->properties[$key] !== $value) {
				if (!in_array($key, $this->updatedProperties)) {
					$this->updatedProperties[] = $key;
				}
				$this->properties[$key] = $value;
			}
		}
		// If the mime_type property should be updated and it was changed also update the type.
		if (array_key_exists('mime_type', $properties) && in_array('mime_type', $this->updatedProperties)) {
			$this->updatedProperties[] = 'type';
			unset($this->properties['type']);
			$this->getType();
		}
		if (array_key_exists('storage', $properties) && in_array('storage', $this->updatedProperties)) {
			$this->storage = ResourceFactory::getInstance()->getStorageObject($properties['storage']);
		}
	}

	/**
	 * Updates MetaData properties
	 *
	 * @internal Do not use outside the FileAbstraction Layer classes
	 *
	 * @param array $properties
	 * @return void
	 */
	public function _updateMetaDataProperties(array $properties) {
		if ($this->metaDataProperties !== NULL) {
			$this->metaDataProperties = array_merge($this->metaDataProperties, $properties);
		} else {
			$this->metaDataProperties = $properties;
		}
	}

	/**
	 * Returns the names of all properties that have been updated in this record
	 *
	 * @return array
	 */
	public function getUpdatedProperties() {
		return $this->updatedProperties;
	}

	/****************************************
	 * STORAGE AND MANAGEMENT RELATED METHODS
	 ****************************************/
	/**
	 * Check if a file operation (= action) is allowed for this file
	 *
	 * @param 	string	$action, can be read, write, delete
	 * @return boolean
	 */
	public function checkActionPermission($action) {
		return $this->getStorage()->checkFileActionPermission($action, $this);
	}

	/*****************
	 * SPECIAL METHODS
	 *****************/
	/**
	 * Creates a MD5 hash checksum based on the combined identifier of the file,
	 * the files' mimetype and the systems' encryption key.
	 * used to generate a thumbnail, and this hash is checked if valid
	 *
	 * @return string the MD5 hash
	 */
	public function calculateChecksum() {
		return md5(
			$this->getCombinedIdentifier() . '|' .
			$this->getMimeType() . '|' .
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
		);
	}

	/**
	 * Returns a modified version of the file.
	 *
	 * @param string $taskType The task type of this processing
	 * @param array $configuration the processing configuration, see manual for that
	 * @return ProcessedFile The processed file
	 */
	public function process($taskType, array $configuration) {
		return $this->getStorage()->processFile($this, $taskType, $configuration);
	}

	/**
	 * Returns an array representation of the file.
	 * (This is used by the generic listing module vidi when displaying file records.)
	 *
	 * @return array Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
	 */
	public function toArray() {
		$array = array(
			'id' => $this->getCombinedIdentifier(),
			'name' => $this->getName(),
			'extension' => $this->getExtension(),
			'type' => $this->getType(),
			'mimetype' => $this->getMimeType(),
			'size' => $this->getSize(),
			'url' => $this->getPublicUrl(),
			'indexed' => TRUE,
			'uid' => $this->getUid(),
			'permissions' => array(
				'read' => $this->checkActionPermission('read'),
				'write' => $this->checkActionPermission('write'),
				'delete' => $this->checkActionPermission('delete')
			),
			'checksum' => $this->calculateChecksum()
		);
		foreach ($this->properties as $key => $value) {
			$array[$key] = $value;
		}
		$stat = $this->getStorage()->getFileInfo($this);
		foreach ($stat as $key => $value) {
			$array[$key] = $value;
		}
		return $array;
	}

	/**
	 * @return boolean
	 */
	public function isMissing() {
		return (bool) $this->getProperty('missing');
	}

	/**
	 * @param boolean $missing
	 */
	public function setMissing($missing) {
		$this->updateProperties(array('missing' => $missing ? 1 : 0));
	}

	/**
	 * Returns a publicly accessible URL for this file
	 * When file is marked as missing or deleted no url is returned
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g. some
	 * web-based authentication. You have to take care of this yourself.
	 *
	 * @param bool  $relativeToCurrentScript   Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 *
	 * @return string
	 */
	public function getPublicUrl($relativeToCurrentScript = FALSE) {
		if ($this->isMissing() || $this->deleted) {
			return FALSE;
		} else {
			return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
		}
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
	 */
	protected function getMetaDataRepository() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
	 */
	protected function getFileIndexRepository() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
	}

	/**
	 * Internal function to retrieve the indexer service,
	 * if it does not exist, an instance will be created
	 *
	 * @return Index\Indexer
	 */
	protected function getIndexerService() {
		if ($this->indexerService === NULL) {
			$this->indexerService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\Indexer', $this->storage);
		}
		return $this->indexerService;
	}

	/**
	 * @param boolean $indexingState
	 * @internal Only for usage in Indexer
	 */
	public function setIndexingInProgess($indexingState) {
		$this->indexingInProgress = (boolean)$indexingState;
	}

	/**
	 * @param $key
	 * @internal Only for use in Repositories and indexer
	 * @return mixed
	 */
	public function _getPropertyRaw($key) {
		return parent::getProperty($key);
	}
}
