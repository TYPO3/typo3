<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Benjamin Mack <benni@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Representation of a specific processed version of a file. These are created by the FileProcessingService,
 * which in turn uses helper classes for doing the actual file processing. See there for a detailed description.
 *
 * Objects of this class may be freshly created during runtime or being fetched from the database. The latter
 * indicates that the file has been processed earlier and was then cached.
 *
 * Each processed file—besides belonging to one file—has been created for a certain task (context) and
 * configuration. All these won't change during the lifetime of a processed file; the only thing
 * that can change is the original file, or rather it's contents. In that case, the processed file has to
 * be processed again. Detecting this is done via comparing the current SHA1 hash of the original file against
 * the one it had at the time the file was processed.
 * The configuration of a processed file indicates what should be done to the original file to create the
 * processed version. This may include things like cropping, scaling, rotating, flipping or using some special
 * magic.
 * A file may also meet the expectations set in the configuration without any processing. In that case, the
 * ProcessedFile object still exists, but there is no physical file directly linked to it. Instead, it then
 * redirects most method calls to the original file object. The data of these objects are also stored in the
 * database, to indicate that no processing is required. With such files, the identifier and name fields in the
 * database are empty to show this.
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class ProcessedFile extends AbstractFile {

	/*********************************************
	 * FILE PROCESSING CONTEXTS
	 *********************************************/
	/**
	 * Basic processing context to get a processed image with smaller
	 * width/height to render a preview
	 */
	const CONTEXT_IMAGEPREVIEW = 'Image.Preview';
	/**
	 * Standard processing context for the frontend, that was previously
	 * in tslib_cObj::getImgResource which only takes cropping, masking and scaling
	 * into account
	 */
	const CONTEXT_IMAGECROPSCALEMASK = 'Image.CropScaleMask';

	/**
	 * Processing context, i.e. the type of processing done
	 *
	 * @var string
	 */
	protected $taskType;

	/**
	 * @var Processing\TaskInterface
	 */
	protected $task;

	/**
	 * @var Processing\TaskTypeRegistry
	 */
	protected $taskTypeRegistry;

	/**
	 * Processing configuration
	 *
	 * @var array
	 */
	protected $processingConfiguration;

	/**
	 * Reference to the original file this processed file has been created from.
	 *
	 * @var File
	 */
	protected $originalFile;

	/**
	 * The SHA1 hash of the original file this processed version has been created for.
	 * Is used for detecting changes if the original file has been changed and thus
	 * we have to recreate this processed file.
	 *
	 * @var string
	 */
	protected $originalFileSha1;

	/**
	 * A flag that shows if this object has been updated during its lifetime, i.e. the file has been
	 * replaced with a new one.
	 *
	 * @var boolean
	 */
	protected $updated = FALSE;

	/**
	 * Constructor for a processed file object. Should normally not be used
	 * directly, use the corresponding factory methods instead.
	 *
	 * @param File $originalFile
	 * @param string $taskType
	 * @param array $processingConfiguration
	 * @param array $databaseRow
	 */
	public function __construct(File $originalFile, $taskType, array $processingConfiguration, array $databaseRow = NULL) {
		$this->originalFile = $originalFile;
		$this->storage = $originalFile->getStorage();
		$this->taskType = $taskType;
		$this->processingConfiguration = $processingConfiguration;
		if (is_array($databaseRow)) {
			$this->reconstituteFromDatabaseRecord($databaseRow);
		}
		$this->taskTypeRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Processing\\TaskTypeRegistry');
	}

	/**
	 * Creates a ProcessedFile object from a database record.
	 *
	 * @param array $databaseRow
	 * @return ProcessedFile
	 */
	protected function reconstituteFromDatabaseRecord(array $databaseRow) {
		$this->taskType = empty($this->taskType) ? $databaseRow['task_type'] : $this->taskType;
		$this->processingConfiguration = empty($this->processingConfiguration) ? unserialize($databaseRow['configuration']) : $this->processingConfiguration;

		$this->originalFileSha1 = $databaseRow['originalfilesha1'];
		$this->identifier = $databaseRow['identifier'];
		$this->name = $databaseRow['name'];
		$this->properties = $databaseRow;
	}

	/********************************
	 * VARIOUS FILE PROPERTY GETTERS
	 ********************************/

	/**
	 * Returns a unique checksum for this file's processing configuration and original file.
	 *
	 * @return string
	 */
	// TODO replace these usages with direct calls to the task object
	public function calculateChecksum() {
		return $this->getTask()->getConfigurationChecksum();
	}

	/*******************
	 * CONTENTS RELATED
	 *******************/
	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return File The file object (allows chaining).
	 * @throws \BadMethodCallException
	 */
	public function setContents($contents) {
		throw new \BadMethodCallException('Setting contents not possible for processed file.', 1305438528);
	}

	/**
	 * Injects a local file, which is a processing result into the object.
	 *
	 * @param string $filePath
	 * @return void
	 * @throws \RuntimeException
	 */
	public function updateWithLocalFile($filePath) {
		if ($this->identifier === NULL) {
			throw new \RuntimeException('Cannot update original file!', 1350582054);
		}
		// TODO this should be more generic (in fact it only works for local file paths)
		$addedFile = $this->storage->addFile($filePath, $this->storage->getProcessingFolder(), $this->name, 'replace');
		$addedFile->setIndexable(FALSE);

		// Update some related properties
		$this->identifier = $addedFile->getIdentifier();
		$this->originalFileSha1 = $this->originalFile->getSha1();
		$this->updateProperties($addedFile->getProperties());
		$this->deleted = FALSE;
		$this->updated = TRUE;
	}

	/*****************************************
	 * STORAGE AND MANAGEMENT RELATED METHDOS
	 *****************************************/
	/**
	 * Returns TRUE if this file is indexed
	 *
	 * @return boolean
	 */
	public function isIndexed() {
		// Processed files are never indexed; instead you might be looking for isPersisted()
		return FALSE;
	}

	/**
	 * Checks whether the ProcessedFile already has an entry in sys_file_processedfile table
	 *
	 * @return boolean
	 */
	public function isPersisted() {
		return is_array($this->properties) && array_key_exists('uid', $this->properties) && $this->properties['uid'] > 0;
	}

	/**
	 * Checks whether the ProcessedFile Object is newly created
	 *
	 * @return boolean
	 */
	public function isNew() {
		return !$this->isPersisted();
	}

	/**
	 * Checks whether the object since last reconstitution, and therefore
	 * needs persistence again
	 *
	 * @return boolean
	 */
	public function isUpdated() {
		return $this->updated;
	}

	/**
	 * Sets a new file name
	 *
	 * @param string $name
	 */
	public function setName($name) {
		// Remove the existing file
		if ($this->name !== $name && $this->name !== '' && $this->exists()) {
			$this->delete();
		}

		$this->name = $name;
		// TODO this is a *weird* hack that will fail if the storage is non-hierarchical!
		$this->identifier = $this->storage->getProcessingFolder()->getIdentifier() . $this->name;

		$this->updated = TRUE;
	}

	/******************
	 * SPECIAL METHODS
	 ******************/

	/**
	 * Returns TRUE if this file is already processed.
	 *
	 * @return boolean
	 */
	public function isProcessed() {
		return ($this->isPersisted() && !$this->needsReprocessing()) || $this->updated;
	}

	/**
	 * Getter for the Original, unprocessed File
	 *
	 * @return File
	 */
	public function getOriginalFile() {
		return $this->originalFile;
	}

	/**
	 * Get the identifier of the file
	 *
	 * If there is no processed file in the file system  (as the original file did not have to be modified e.g.
	 * when the original image is in the boundaries of the maxW/maxH stuff), then just return the identifier of
	 * the original file
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return (!$this->usesOriginalFile()) ? $this->identifier : $this->getOriginalFile()->getIdentifier();
	}

	/**
	 * Get the name of the file
	 *
	 * If there is no processed file in the file system (as the original file did not have to be modified e.g.
	 * when the original image is in the boundaries of the maxW/maxH stuff)
	 * then just return the name of the original file
	 *
	 * @return string
	 */
	public function getName() {
		if ($this->usesOriginalFile()) {
			return $this->originalFile->getName();
		} else {
			return $this->name;
		}
	}

	/**
	 * Updates properties of this object. Do not use this to reconstitute an object from the database; use
	 * reconstituteFromDatabaseRecord() instead!
	 *
	 * @param array $properties
	 */
	public function updateProperties(array $properties) {
		if (!is_array($this->properties)) {
			$this->properties = array();
		}

		if (array_key_exists('uid', $properties) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($properties['uid'])) {
			$this->properties['uid'] = $properties['uid'];
		}

		// TODO we should have a blacklist of properties that might not be updated
		$this->properties = array_merge($this->properties, $properties);

		// TODO when should this update be done?
		if (!$this->isUnchanged() && $this->exists()) {
			$this->properties = array_merge($this->properties, $this->storage->getFileInfo($this));
		}

	}

	/**
	 * Basic array function for the DB update
	 *
	 * @return array
	 */
	public function toArray() {
		if ($this->usesOriginalFile()) {
			$properties = $this->originalFile->getProperties();
			unset($properties['uid']);
			unset($properties['pid']);
			unset($properties['identifier']);
			unset($properties['name']);
			unset($properties['width']);
			unset($properties['height']);
		} else {
			$properties = $this->properties;
			$properties['identifier'] = $this->getIdentifier();
			$properties['name'] = $this->getName();
		}

		return array_merge($properties, array(
			'storage' => $this->getStorage()->getUid(),
			'checksum' => $this->calculateChecksum(),
			'task_type' => $this->taskType,
			'configuration' => serialize($this->processingConfiguration),
			'original' => $this->originalFile->getUid(),
			'originalfilesha1' => $this->originalFileSha1
		));
	}

	/**
	 * Returns TRUE if this file has not been changed during processing (i.e., we just deliver the original file)
	 *
	 * @return boolean
	 */
	protected function isUnchanged() {
		return $this->identifier == NULL || $this->identifier === $this->originalFile->getIdentifier();
	}

	/**
	 * @return void
	 */
	public function setUsesOriginalFile() {
		// TODO check if some of these properties can/should be set in a generic update method
		$this->identifier = $this->originalFile->getIdentifier();
		$this->updated = TRUE;
		$this->originalFileSha1 = $this->originalFile->getSha1();
	}

	/**
	 * @return boolean
	 */
	public function usesOriginalFile() {
		return $this->isUnchanged();
	}

	/**
	 * Returns TRUE if the original file of this file changed and the file should be processed again.
	 *
	 * @return boolean
	 */
	public function isOutdated() {
		return $this->needsReprocessing();
	}

	/**
	 * @return boolean
	 */
	public function delete() {
		if ($this->isUnchanged()) {
			return FALSE;
		}
		return parent::delete();
	}

	/**
	 * Getter for file-properties
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getProperty($key) {
		// The uid always (!) has to come from this file and never the original file (see getOriginalFile() to get this)
		if ($this->isUnchanged() && $key !== 'uid') {
			return $this->originalFile->getProperty($key);
		} else {
			return $this->properties[$key];
		}
	}

	/**
	 * Returns the uid of this file
	 *
	 * @return int
	 */
	public function getUid() {
		return $this->properties['uid'];
	}


	/**
	 * Checks if the ProcessedFile needs reprocessing
	 *
	 * @return boolean
	 */
	public function needsReprocessing() {
		$fileMustBeRecreated = FALSE;

		// processedFile does not exist
		if (!$this->usesOriginalFile() && !$this->exists()) {
			$fileMustBeRecreated = TRUE;
		}

		// hash does not match
		if (array_key_exists('checksum', $this->properties) && $this->calculateChecksum() !== $this->properties['checksum'])  {
			$fileMustBeRecreated = TRUE;
		}

		// original file changed
		if ($this->originalFile->getSha1() !== $this->originalFileSha1) {
			$fileMustBeRecreated = TRUE;
		}

		if (!array_key_exists('uid', $this->properties)) {
			$fileMustBeRecreated = TRUE;
		}

		// remove outdated file
		if ($fileMustBeRecreated && $this->exists()) {
			$this->delete();
		}
		return $fileMustBeRecreated;
	}

	/**
	 * Returns the processing information
	 *
	 * @return array
	 */
	public function getProcessingConfiguration() {
		return $this->processingConfiguration;
	}

	/**
	 * Getter for the task identifier.
	 *
	 * @return string
	 */
	public function getTaskIdentifier() {
		return $this->taskType;
	}

	/**
	 * Returns the task object associated with this processed file.
	 *
	 * @return Processing\TaskInterface
	 * @throws \RuntimeException
	 */
	public function getTask() {
		if ($this->task == NULL) {
			$this->task = $this->taskTypeRegistry->getTaskForType($this->taskType, $this, $this->processingConfiguration);
		}

		return $this->task;
	}

	/**
	 * Generate the name of of the new File
	 *
	 * @return string
	 */
	public function generateProcessedFileNameWithoutExtension() {
		$name = $this->originalFile->getNameWithoutExtension();
		$name .= '_' . $this->originalFile->getUid();
		$name .= '_' . $this->calculateChecksum();

		return $name;
	}

}

?>
