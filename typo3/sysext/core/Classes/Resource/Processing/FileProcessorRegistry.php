<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Frans Saris <franssaris@gmail.com>
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

/**
 * Registry for File Processors
 */
class FileProcessorRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Registered FileProcessor ClassNames
	 *
	 * @var array
	 */
	protected $fileProcessors = array();

	/**
	 * Instance Cache for Processors
	 *
	 * @var array FileProcessorInterface[]
	 */
	protected $instances;

	/**
	 * Returns an instance of this class
	 *
	 * @return FileProcessorRegistry
	 */
	public static function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorRegistry');
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['fileProcessors'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['fileProcessors'] as $class) {
				$this->registerFileProcessorClass($class);
			}
		}
	}

	/**
	 * Allows to register a FileProcessor
	 *
	 * @param string $className
	 * @throws \InvalidArgumentException
	 */
	public function registerFileProcessorClass($className) {
		if (!class_exists($className)) {
			throw new \InvalidArgumentException('The Class you are registering is not available', 1393614709);
		} elseif (!in_array('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', class_implements($className))) {
			throw new \InvalidArgumentException('The Processor needs to implement the FileProcessorInterface', 1393614710);
		} else {
			$this->fileProcessors[] = $className;
		}
	}

	/**
	 * Get all registered processors
	 *
	 * @return array FileProcessorInterface[]
	 */
	public function getFileProcessors() {
		if ($this->instances === NULL) {
			$this->instances = array();

				// as the result is in reverse order we need to reverse
				// the array before processing to keep the items with same
				// priority in the same order as they were added to the registry.
			$fileProcessors = array_reverse($this->fileProcessors);
			foreach ($fileProcessors as $className) {
				/** @var FileProcessorInterface $object */
				$object = $this->createFileProcessorInstance($className);
				$this->instances[] = $object;
			}

			if (count($this->instances) > 1) {
				usort($this->instances, array($this, 'compareProcessorPriority'));
			}
		}
		return $this->instances;
	}

	/**
	 * Create an instance of a certain FileProcessor class
	 *
	 * @param $className
	 * @return FileProcessorInterface
	 */
	protected function createFileProcessorInstance($className) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
	}

	/**
	 * Compare the priority of 2 File Processors
	 * Is used for sorting array of FileProcessor instances by priority
	 * We want the result to be ordered from high to low so a higher
	 * priority comes before a lower.
	 *
	 * @param FileProcessorInterface $processorA
	 * @param FileProcessorInterface $processorB
	 * @return int -1 a > b, 0 a == b, 1 a < b
	 */
	protected function compareProcessorPriority(FileProcessorInterface $processorA, FileProcessorInterface $processorB) {
		$return = 0;
		if ($processorA->getPriority() < $processorB->getPriority()) {
			$return = 1;
		} elseif ($processorA->getPriority() > $processorB->getPriority()) {
			$return = -1;
		}
		return $return;
	}

	/**
	 * Get FileProcessors which work for a special driver
	 *
	 * @param string $driverType
	 * @return array FileProcessorInterface[]
	 */
	public function getFileProcessorsWithDriverSupport($driverType) {
		$allProcessors = $this->getFileProcessors();
		$filteredProcessors = array();

		/** @var FileProcessorInterface $processorObject */
		foreach ($allProcessors as $processorObject) {
			if ($processorObject->getDriverRestrictions() === array()) {
				$filteredProcessors[] = $processorObject;
			} elseif (in_array($driverType, $processorObject->getDriverRestrictions())) {
				$filteredProcessors[] = $processorObject;
			}
		}
		return $filteredProcessors;
	}

	/**
	 * Get matching file processor with highest priority
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param ProcessingRequestInterface $processingRequest
	 * @return NULL|FileProcessorInterface
	 */
	public function getProcessorFor(\TYPO3\CMS\Core\Resource\FileInterface $file, ProcessingRequestInterface $processingRequest) {
		$driverType = $file->getStorage()->getDriverType();
		$matchingFileProcessor = NULL;

		/** @var FileProcessorInterface $fileProcessor */
		foreach ($this->getFileProcessorsWithDriverSupport($driverType) as $fileProcessor) {
			if ($fileProcessor->canProcess($file, $processingRequest)) {
				$matchingFileProcessor = $fileProcessor;
				break;
			}
		}
		return $matchingFileProcessor;
	}
}