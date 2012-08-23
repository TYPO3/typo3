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
 * Representation of a specific processing of a file.
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
	 * in tslib_cObj::getImgResource which only takes croping, masking and scaling
	 * into account
	 */
	const CONTEXT_IMAGECROPSCALEMASK = 'image.cropscalemask';
	/**
	 * Processing context
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * check if the file is processed
	 *
	 * @var boolean
	 */
	protected $processed;

	/**
	 * Processing configuration
	 *
	 * @var array
	 */
	protected $processingConfiguration;

	/**
	 * Reference to the original File object underlying this FileReference.
	 *
	 * @var \TYPO3\CMS\Core\Resource\File
	 */
	protected $originalFile;

	/**
	 * Constructor for a file processing object. Should normally not be used
	 * directly, use the corresponding factory methods instead.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $originalFile
	 * @param string $context
	 * @param array $processingConfiguration
	 */
	public function __construct(\TYPO3\CMS\Core\Resource\File $originalFile, $context, array $processingConfiguration) {
		$this->originalFile = $originalFile;
		$this->context = $context;
		$this->processingConfiguration = $processingConfiguration;
	}

	/*******************************
	 * VARIOUS FILE PROPERTY GETTERS
	 ************************/
	/**
	 * Returns the checksum that makes the processed configuration unique
	 * also takes the mtime and the uid into account, to find out if the
	 * process needs to be done again
	 *
	 * @return string
	 */
	public function calculateChecksum() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(((($this->originalFile->getUid() . $this->originalFile->getModificationTime()) . $this->context) . serialize($GLOBALS['TYPO3_CONF_VARS']['GFX'])) . serialize($this->processingConfiguration));
	}

	/******************
	 * CONTENTS RELATED
	 ******************/
	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return \TYPO3\CMS\Core\Resource\File The file object (allows chaining).
	 */
	public function setContents($contents) {
		throw new \BadMethodCallException('Setting contents not possible for processed file.', 1305438528);
	}

	/****************************************
	 * STORAGE AND MANAGEMENT RELATED METHDOS
	 ****************************************/
	/**
	 * Returns TRUE if this file is indexed
	 *
	 * @return boolean
	 */
	public function isIndexed() {
		return FALSE;
	}

	/*****************
	 * SPECIAL METHODS
	 *****************/
	/**
	 * Returns TRUE if this file is already processed.
	 *
	 * @return boolean
	 */
	public function isProcessed() {
		return (bool) $this->processed;
	}

	/**
	 * Called when the processed file is processed
	 *
	 * @param boolean $isProcessed
	 * @return void
	 */
	public function setProcessed($isProcessed) {
		$this->processed = (bool) $isProcessed;
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	public function getOriginalFile() {
		return $this->originalFile;
	}

	/**
	 * get the identifier of the file
	 * if there is no processed file in the file system  (as the original file did not have to be modified e.g.
	 * when the original image is in the boundaries of the maxW/maxH stuff)
	 * then just return the identifier of the original file
	 *
	 * @return string
	 */
	public function getIdentifier() {
		if ($this->identifier) {
			return $this->identifier;
		} else {
			return $this->originalFile->getIdentifier();
		}
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
		if ($this->name) {
			return $this->name;
		} else {
			return $this->originalFile->getName();
		}
	}

	/**
	 * Updates properties of this object.
	 * This method is used to reconstitute settings from the
	 * database into this object after being intantiated.
	 *
	 * @param array $properties
	 */
	public function updateProperties(array $properties) {
		if ($properties['name']) {
			$this->name = $properties['name'];
		}
		if ($properties['identifier']) {
			$this->identifier = $properties['identifier'];
		}
		if (isset($properties['is_processed']) && $properties['is_processed'] > 0) {
			$this->processed = TRUE;
		}
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($properties['storage'])) {
			$this->setStorage($properties['storage']);
		}
		$this->properties = array_merge($this->properties, $properties);
	}

	/**
	 * basic array function for the DB update
	 *
	 * @return array
	 */
	public function toArray() {
		// @todo: define what we need here
		return array(
			'storage' => $this->getStorage()->getUid(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'is_processed' => intval($this->processed),
			'checksum' => $this->calculateChecksum(),
			'context' => $this->context,
			'configuration' => serialize($this->processingConfiguration),
			'original' => $this->originalFile->getUid(),
			'width' => $this->getProperty('width'),
			'height' => $this->getProperty('height')
		);
	}

}


?>