<?php
namespace TYPO3\CMS\Core\Resource\Processing;
use \TYPO3\CMS\Core\Utility, \TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Generic factory for all objects related to file processing, e.g. ProcessedFiles, Tasks and Processors.
 */
class ProcessingFactory {

	/**
	 * @var Resource\ResourceFactory
	 */
	protected $resourceFactory;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->resourceFactory = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
	}

	/**
	 * Generates a new ProcessedFile object; additionally checks if this processed file already exists in the DB
	 * (i.e. has been processed before)
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $originalFileObject
	 * @param string $task The task to process on the file
	 * @param array $configuration The processing configuration
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function getProcessedFileObject(\TYPO3\CMS\Core\Resource\FileInterface $originalFileObject, $taskType, array $configuration) {
		/* @var \TYPO3\CMS\Core\Resource\ProcessedFileRepository $repository */
		$repository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
		// Check if this file already exists in the DB
		$processedFileObject = $repository->findOneByOriginalFileAndTaskTypeAndConfiguration($originalFileObject, $taskType, $configuration);
		return $processedFileObject;
	}

	/**
	 * Create a ProcessedFile object from a raw database record.
	 *
	 * @param array $data
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function createProcessedFileObjectFromDatabase(array $data) {
		$originalFile = $this->resourceFactory->getFileObject(intval($data['original']));
		$originalFile->setStorage($this->resourceFactory->getStorageObject($originalFile->getProperty('storage')));

		$processedFileObject = \TYPO3\CMS\Core\Resource\ProcessedFile::reconstituteFromDatabaseRecord($originalFile, $data);

		return $processedFileObject;
	}

	/**
	 * Creates a ProcessedFile object from a file object and a processing configuration
	 *
	 * @param Resource\FileInterface $originalFileObject
	 * @param string $taskType
	 * @param array $configuration
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function createNewProcessedFileObject(Resource\FileInterface $originalFileObject, $taskType, array $configuration) {
		$processedFileObject = Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Resource\\ProcessedFile',
			$originalFileObject,
			$taskType,
			$configuration
		);
		return $processedFileObject;
	}
}

?>