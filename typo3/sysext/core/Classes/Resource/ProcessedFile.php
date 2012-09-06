<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Benjamin Mack <benni@typo3.org>
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
 * Representation of a specific processed version of a file.
 *
 * @author Benjamin Mack <benni@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class ProcessedFile extends \TYPO3\CMS\Core\Resource\AbstractFile {

	/*********************************************
	 * FILE PROCESSING CONTEXTS
	 *********************************************/
	/**
	 * Basic processing context to get a processed image with smaller
	 * width/height to render a preview
	 */
	const CONTEXT_IMAGEPREVIEW = 'image.preview';
	/**
	 * Standard processing context for the frontend, that was previously
	 * in tslib_cObj::getImgResource which only takes cropping, masking and scaling
	 * into account
	 */
	const CONTEXT_IMAGECROPSCALEMASK = 'image.cropscalemask';

	/**
	 * Processing context, i.e. the type of processing done
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * Processing configuration
	 *
	 * @var array
	 */
	protected $processingConfiguration;

	/**
	 * Reference to the original file this processed file has been created from.
	 *
	 * @var \TYPO3\CMS\Core\Resource\File
	 */
	protected $originalFile;

	/**
	 * Constructor for a processed file object. Should normally not be used
	 * directly, use the corresponding factory methods instead.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $originalFile
	 * @param string $context
	 * @param array $processingConfiguration
	 */
	public function __construct(\TYPO3\CMS\Core\Resource\File $originalFile, $context, array $processingConfiguration) {
		$this->originalFile = $originalFile;
		$this->storage = $originalFile->getStorage();
		$this->context = $context;
		$this->processingConfiguration = $processingConfiguration;
	}

	/********************************
	 * VARIOUS FILE PROPERTY GETTERS
	 ********************************/

	/**
	 * Returns the checksum that makes the processed configuration unique
	 * also takes the mtime and the uid into account, to find out if the
	 * process needs to be done again
	 *
	 * @return string
	 */
	public function calculateChecksum() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(
			$this->getOriginalFile()->getUid() . '|' .
			$this->context . '|' .
			serialize($GLOBALS['TYPO3_CONF_VARS']['GFX']) . '|' .
			serialize($this->processingConfiguration)
			// TODO why was modificationTime of original file removed here?
		);
	}

	/*******************
	 * CONTENTS RELATED
	 *******************/
	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return \TYPO3\CMS\Core\Resource\File The file object (allows chaining).
	 * @throws \BadMethodCallException
	 */
	public function setContents($contents) {
		throw new \BadMethodCallException('Setting contents not possible for processed file.', 1305438528);
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
		// Processed files are never indexed
		return FALSE;
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
		return !$this->needsReprocessing();
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\File
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
		return ($this->identifier != '') ? $this->identifier : $this->getOriginalFile()->getIdentifier();
	}

	/**
	 * get the name of the file
	 * if there is no processed file in the file system (as the original file did not have to be modified e.g.
	 * when the original image is in the boundaries of the maxW/maxH stuff)
	 * then just return the name of the original file
	 *
	 * @return string
	 */
	public function getName() {
		if ($this->isUnchanged()) {
			return $this->originalFile->getName();
		} else {
			return $this->name;
		}
	}

	/**
	 * Updates properties of this object.
	 *
	 * This method is used to reconstitute settings from the database into this object after being instantiated.
	 *
	 * @param array $properties
	 */
	public function updateProperties(array $properties) {
		if (!is_array($this->properties)) {
			$this->properties = array();
		}

		if ($properties['name']) {
			$this->name = $properties['name'];
		}

		if ($properties['identifier']) {
			$this->identifier = $properties['identifier'];
		}

		if ($properties['tstamp']) {
			// TODO modification_date is no property of sys_file_processedfile; additionally, it is the update time of
			// the record, not the file -> what do we need/want here?
			$properties['modification_date'] = $properties['tstamp'];
		}

		if ($properties['configuration']) {
			$this->processingConfiguration = unserialize($properties['configuration']);
		}

		$this->properties = array_merge($this->properties, $properties);
		if (!$this->isUnchanged() && $this->exists()) {
			$this->properties = array_merge($this->properties, $this->storage->getFileInfo($this));
		}

	}

	/**
	 * basic array function for the DB update
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'storage' => $this->getStorage()->getUid(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'checksum' => $this->calculateChecksum(),
			'context' => $this->context,
			'configuration' => serialize($this->processingConfiguration),
			'original' => $this->originalFile->getUid(),
		);
	}

	/**
	 * Returns TRUE if this file has not been changed during processing (i.e., we just deliver the original file)
	 *
	 * @return bool
	 */
	protected function isUnchanged() {
		return $this->identifier == $this->originalFile->getIdentifier();
	}

	/**
	 * @return bool
	 */
	public function delete() {
		if (!$this->isUnchanged()) {
			return parent::delete();
		}
		return FALSE;
	}


	/**
	 * Checks if the ProcessedFile needs reprocessing
	 *
	 * @return bool
	 */
	public function needsReprocessing() {
		$fileMustBeRecreated = FALSE;

			// processedFile does not exist
		if (!$this->isUnchanged() && !$this->exists()) {
			$fileMustBeRecreated = TRUE;
		}

			// hash does not match
		if ($this->hasProperty('checksum') && $this->calculateChecksum() !== $this->getProperty('checksum'))  {
			$fileMustBeRecreated = TRUE;
		}

			// original file changed
		if ($this->getModificationTime() !== NULL && $this->getModificationTime() < $this->getOriginalFile()->getModificationTime()) {
			$fileMustBeRecreated = TRUE;
		}

			// remove outdated file
		if ($fileMustBeRecreated && $this->exists()) {
			$this->delete();
		}
		return $fileMustBeRecreated;
	}

	/**
	 * @return array
	 */
	public function getProcessingConfiguration() {
		return $this->processingConfiguration;
	}

	/**
	 * @return string
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * @return string
	 */
	public function generateProcessedFileNameWithoutExtension() {
		$name = '';
		if ($this->context == self::CONTEXT_IMAGEPREVIEW) {
			$name .= 'thumbnail_';
		}

		$name = $this->originalFile->getNameWithoutExtension();
		$name .= '_' . $this->originalFile->getUid();
		$name .= '_' . $this->calculateChecksum();

		return $name;
	}

}


?>