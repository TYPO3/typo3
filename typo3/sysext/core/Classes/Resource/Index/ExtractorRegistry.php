<?php
namespace TYPO3\CMS\Core\Resource\Index;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Registry for MetaData extraction Services
 *
 */
class ExtractorRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Registered ClassNames
	 * @var array
	 */
	protected $extractors = array();

	/**
	 * Instance Cache for Extractors
	 *
	 * @var ExtractorInterface[]
	 */
	protected $instances = NULL;

	/**
	 * Returns an instance of this class
	 *
	 * @return ExtractorRegistry
	 */
	public static function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\ExtractorRegistry');
	}

	/**
	 * Allows to register MetaData extraction to the FAL Indexer
	 *
	 * @param string $className
	 * @throws \RuntimeException
	 */
	public function registerExtractionService($className) {
		if (!class_exists($className)) {
			throw new \RuntimeException('The Class you are registering is not available');
		} elseif (!in_array('TYPO3\\CMS\\Core\\Resource\\Index\\ExtractorInterface', class_implements($className))) {
			throw new \RuntimeException('The extractor needs to implement the ExtractorInterface');
		} else {
			$this->extractors[] = $className;
		}
	}

	/**
	 * Get all registered extractors
	 *
	 * @return ExtractorInterface[]
	 */
	public function getExtractors() {
		if ($this->instances === NULL) {
			$this->instances = array();
			foreach ($this->extractors as $className) {
				/** @var ExtractorInterface $object */
				$object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
				$this->instances[$object->getExecutionPriority()] = $object;
			}
			krsort($this->instances);
		}
		return $this->instances;
	}

	/**
	 * Get Extractors which work for a special driver
	 *
	 * @param string $driverType
	 * @return ExtractorInterface[]
	 */
	public function getExtractorsWithDriverSupport($driverType) {
		$allExtractors = $this->getExtractors();

		$filteredExtractors = array();
		foreach ($allExtractors as $priority => $extractorObject) {
			if (count($extractorObject->getDriverRestrictions()) == 0) {
				$filteredExtractors[$priority] = $extractorObject;
			} elseif (in_array($driverType, $extractorObject->getDriverRestrictions())) {
				$filteredExtractors[$priority] = $extractorObject;
			}
		}
		return $filteredExtractors;
	}

}
