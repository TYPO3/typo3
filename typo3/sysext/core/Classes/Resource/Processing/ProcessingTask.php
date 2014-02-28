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
 * ProcessingTask for processing multiple file processing requests
 */
class ProcessingTask {

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileInterface
	 */
	protected $originalFile;

	/**
	 * @var array ProcessingRequestInterface[]
	 */
	protected $processingRequests;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $originalFile
	 * @param array $processingRequests
	 */
	public function __construct(\TYPO3\CMS\Core\Resource\FileInterface $originalFile, array $processingRequests) {
		$this->originalFile = $originalFile;

		$this->callProcessingHook('preProcessingRequests', $processingRequests);
		$this->processingRequests = $processingRequests;
		$this->callProcessingHook('postProcessingRequests', $processingRequests);
	}

	/**
	 * Process all requests
	 *
	 * @return null|\TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function process() {
		$processedFile = NULL;
		$fileProcessorRegistry = FileProcessorRegistry::getInstance();

		foreach ($this->processingRequests as $processingRequest) {
			$processor = $fileProcessorRegistry->getProcessorFor($processedFile ?: $this->originalFile, $processingRequest);
			if ($processor !== NULL) {
				$processedFile = $processor->process($processedFile ?: $this->originalFile, $processingRequest);
			}
		}

		return $processedFile;
	}

	/**
	 * Call processing Hook
	 *
	 * @param string $name name of the hook
	 * @param array $processingRequests processing requests by reference
	 * @throws \UnexpectedValueException
	 */
	public function callProcessingHook($name, array &$processingRequests) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ProcessingTask'][$name])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ProcessingTask'][$name] as $classRef) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (!$hookObject instanceof ProcessingTaskCustomizationHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Core\\Resource\\Processing\\ProcessingTaskCustomizationHookInterface', 1393686550);
				}
				$hookObject->manipulateRequests($this->originalFile, $processingRequests);
			}
		}
	}
}