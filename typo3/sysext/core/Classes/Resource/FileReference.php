<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Ingmar Schlecht <ingmar@typo3.org>
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
 * Representation of a specific usage of a file with possibilities to override certain
 * properties of the original file just for this usage of the file.
 *
 * It acts as a decorator over the original file in the way that most method calls are
 * directly passed along to the original file object.
 *
 * All file related methods are directly passed along; only meta data functionality is adopted
 * in this decorator class to priorities possible overrides for the metadata for this specific usage
 * of the file.
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class FileReference implements FileInterface {

	/**
	 * Various properties of the FileReference. Note that these information can be different
	 * to the ones found in the originalFile.
	 *
	 * @var array
	 */
	protected $propertiesOfFileReference;

	/**
	 * The identifier of this file to identify it on the storage.
	 * On some drivers, this is the path to the file, but drivers could also just
	 * provide any other unique identifier for this file on the specific storage.
	 *
	 * @var string
	 */
	protected $uidOfFileReference;

	/**
	 * The file name of this file. It's either the fileName of the original underlying file,
	 * or the overlay file name supplied by the user for this particular usage (FileReference) of the file.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The FileRepository object. Is needed e.g. for the delete() method to delete the usage record
	 * (sys_file_reference record) of this file usage.
	 *
	 * @var FileRepository
	 */
	protected $fileRepository;

	/**
	 * Reference to the original File object underlying this FileReference.
	 *
	 * @var File
	 */
	protected $originalFile;

	/**
	 * Properties merged with the parent object (File) if
	 * the value is not defined (NULL). Thus, FileReference properties act
	 * as overlays for the defined File properties.
	 *
	 * @var array
	 */
	protected $mergedProperties = array();

	/**
	 * Constructor for a file in use object. Should normally not be used
	 * directly, use the corresponding factory methods instead.
	 *
	 * @param array $fileReferenceData
	 * @param ResourceFactory $factory
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $fileReferenceData, $factory = NULL) {
		$this->propertiesOfFileReference = $fileReferenceData;
		if (!$fileReferenceData['uid_local']) {
			throw new \InvalidArgumentException('Incorrect reference to original file given for FileReference.', 1300098528);
		}
		if (!$factory) {
			/** @var $factory ResourceFactory */
			$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		}
		$this->originalFile = $factory->getFileObject($fileReferenceData['uid_local']);
		$this->fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		if (!is_object($this->originalFile)) {
			throw new \RuntimeException('Original File not found for FileReference.', 1300098529);
		}
		$this->name = $fileReferenceData['name'] !== '' ? $fileReferenceData['name'] : $this->originalFile->getName();
	}

	/*******************************
	 * VARIOUS FILE PROPERTY GETTERS
	 *******************************/
	/**
	 * Returns true if the given key exists for this file.
	 *
	 * @param string $key The property to be looked up
	 * @return boolean
	 */
	public function hasProperty($key) {
		return array_key_exists($key, $this->getProperties());
	}

	/**
	 * Gets a property, falling back to values of the parent.
	 *
	 * @param string $key The property to be looked up
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function getProperty($key) {
		if (!$this->hasProperty($key)) {
			throw new \InvalidArgumentException('Property "' . $key . '" was not found in file reference or original file.', 1314226805);
		}
		$properties = $this->getProperties();
		return $properties[$key];
	}

	/**
	 * Gets a property of the file reference.
	 *
	 * @param string $key The property to be looked up
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function getReferenceProperty($key) {
		if (!array_key_exists($key, $this->propertiesOfFileReference)) {
			throw new \InvalidArgumentException('Property "' . $key . '" of file reference was not found.', 1360684914);
		}
		return $this->propertiesOfFileReference[$key];
	}

	/**
	 * Gets all properties, falling back to values of the parent.
	 *
	 * @return array
	 */
	public function getProperties() {
		if (empty($this->mergedProperties)) {
			$this->mergedProperties = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(
				$this->propertiesOfFileReference,
				$this->originalFile->getProperties(),
				FALSE,
				TRUE,
				FALSE
			);
			array_walk($this->mergedProperties, array($this, 'restoreNonNullValuesCallback'));
		}

		return $this->mergedProperties;
	}

	/**
	 * Callback to handle the NULL value feature
	 *
	 * @param mixed $value
	 * @param mixed $key
	 */
	protected function restoreNonNullValuesCallback(&$value, $key) {
		if (array_key_exists($key, $this->propertiesOfFileReference) && $this->propertiesOfFileReference[$key] !== NULL) {
			$value = $this->propertiesOfFileReference[$key];
		}
	}

	/**
	 * Gets all properties of the file reference.
	 *
	 * @return array
	 */
	public function getReferenceProperties() {
		return $this->propertiesOfFileReference;
	}

	/**
	 * Returns the name of this file
	 *
	 * @return string
	 */
	public function getName() {
		return $this->originalFile->getName();
	}

	/**
	 * Returns the title text to this image
	 *
	 * TODO: Possibly move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->getProperty('title');
	}

	/**
	 * Returns the alternative text to this image
	 *
	 * TODO: Possibly move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getAlternative() {
		return $this->getProperty('alternative');
	}

	/**
	 * Returns the description text to this file
	 *
	 * TODO: Possibly move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->getProperty('description');
	}

	/**
	 * Returns the link that should be active when clicking on this image
	 *
	 * TODO: Move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getLink() {
		return $this->propertiesOfFileReference['link'];
	}

	/**
	 * Returns the uid of this File In Use
	 *
	 * @return integer
	 */
	public function getUid() {
		return (int) $this->propertiesOfFileReference['uid'];
	}

	/**
	 * Returns the size of this file
	 *
	 * @return integer
	 */
	public function getSize() {
		return (int) $this->originalFile->getSize();
	}

	/**
	 * Returns the Sha1 of this file
	 *
	 * @return string
	 */
	public function getSha1() {
		return $this->originalFile->getSha1();
	}

	/**
	 * Get the file extension of this file
	 *
	 * @return string The file extension
	 */
	public function getExtension() {
		return $this->originalFile->getExtension();
	}

	/**
	 * Get the MIME type of this file
	 *
	 * @return array file information
	 */
	public function getMimeType() {
		return $this->originalFile->getMimeType();
	}

	/**
	 * Returns the modification time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getModificationTime() {
		return (int) $this->originalFile->getModificationTime();
	}

	/**
	 * Returns the creation time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getCreationTime() {
		return (int) $this->originalFile->getCreationTime();
	}

	/**
	 * Returns the fileType of this file
	 *
	 * @return integer $fileType
	 */
	public function getType() {
		return (int) $this->originalFile->getType();
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
		return $this->originalFile->getContents();
	}

	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return File The file object (allows chaining).
	 */
	public function setContents($contents) {
		return $this->originalFile->setContents($contents);
	}

	/****************************************
	 * STORAGE AND MANAGEMENT RELATED METHDOS
	 ****************************************/
	/**
	 * Get the storage the original file is located in
	 *
	 * @return ResourceStorage
	 */
	public function getStorage() {
		return $this->originalFile->getStorage();
	}

	/**
	 * Returns the identifier of the underlying original file
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->originalFile->getIdentifier();
	}

	/**
	 * Returns a combined identifier of the underlying original file
	 *
	 * @return string Combined storage and file identifier, e.g. StorageUID:path/and/fileName.png
	 */
	public function getCombinedIdentifier() {
		return $this->originalFile->getCombinedIdentifier();
	}

	/**
	 * Deletes only this particular FileReference from the persistence layer
	 * (database table sys_file_reference) but leaves the original file untouched.
	 *
	 * @throws \BadMethodCallException
	 * @return boolean TRUE if deletion succeeded
	 */
	public function delete() {
		// TODO: Implement this function. This should only delete the
		// FileReference (sys_file_reference) record, not the file itself.
		throw new \BadMethodCallException('Function not implemented FileReference::delete().', 1333754461);
		return $this->fileRepository->removeUsageRecord($this);
	}

	/**
	 * Renames the fileName in this particular usage.
	 *
	 * @param string $newName The new name
	 *
	 * @throws \BadMethodCallException
	 * @return FileReference
	 */
	public function rename($newName) {
		// TODO: Implement this function. This should only rename the
		// FileReference (sys_file_reference) record, not the file itself.
		throw new \BadMethodCallException('Function not implemented FileReference::rename().', 1333754473);
		return $this->fileRepository->renameUsageRecord($this, $newName);
	}

	/*****************
	 * SPECIAL METHODS
	 *****************/
	/**
	 * Returns a publicly accessible URL for this file
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g.
	 * some web-based authentication. You have to take care of this yourself.
	 *
	 * @param bool  $relativeToCurrentScript   Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl($relativeToCurrentScript = FALSE) {
		return $this->originalFile->getPublicUrl($relativeToCurrentScript);
	}

	/**
	 * Returns TRUE if this file is indexed.
	 * This is always true for FileReference objects, as they rely on a
	 * sys_file_reference record to be present, which in turn can only exist if
	 * the original file is indexed.
	 *
	 * @return boolean
	 */
	public function isIndexed() {
		return TRUE;
	}

	/**
	 * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
	 * If the file is normally located on a remote storages, this creates a local copy.
	 * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
	 *
	 * @param boolean $writable Set this to FALSE if you only want to do read operations on the file.
	 * @return string
	 */
	public function getForLocalProcessing($writable = TRUE) {
		return $this->originalFile->getForLocalProcessing($writable);
	}

	/**
	 * Returns an array representation of the file.
	 * (This is used by the generic listing module vidi when displaying file records.)
	 *
	 * @return array Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
	 */
	public function toArray() {
		$array = array_merge($this->originalFile->toArray(), $this->propertiesOfFileReference);
		return $array;
	}

	/**
	 * Gets the original file being referenced.
	 *
	 * @return File
	 */
	public function getOriginalFile() {
		return $this->originalFile;
	}

}


?>