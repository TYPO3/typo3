<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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

use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility;

/**
 * File processing service
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class FileProcessingService {

	/**
	 * @var Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * @var Resource\Driver\AbstractDriver
	 */
	protected $driver;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger
	 */
	protected $logger;

	const SIGNAL_PreFileProcess = 'preFileProcess';
	const SIGNAL_PostFileProcess = 'postFileProcess';

	/**
	 * Creates this object.
	 *
	 * @param Resource\ResourceStorage $storage
	 * @param Resource\Driver\AbstractDriver $driver
	 */
	public function __construct(Resource\ResourceStorage $storage, Resource\Driver\AbstractDriver $driver) {
		$this->storage = $storage;
		$this->driver = $driver;

		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager');
		$this->logger = $logManager->getLogger(__CLASS__);
	}

	/**
	 * Processes a file
	 *
	 * @param Resource\FileInterface $fileObject The file object
	 * @param Resource\ResourceStorage $targetStorage The storage to store the processed file in
	 * @param string $taskType
	 * @param array $configuration
	 *
	 * @return Resource\ProcessedFile
	 * @throws \InvalidArgumentException
	 */
	public function processFile(Resource\FileInterface $fileObject, Resource\ResourceStorage $targetStorage, $taskType, $configuration) {
		/** @var $processedFileRepository Resource\ProcessedFileRepository */
		$processedFileRepository = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');

		$processedFile = $processedFileRepository->findOneByOriginalFileAndTaskTypeAndConfiguration($fileObject, $taskType, $configuration);

		// set the storage of the processed file
		// Pre-process the file
		$this->emitPreFileProcess($processedFile, $fileObject, $taskType, $configuration);

		// Only handle the file if it is not processed yet
		// (maybe modified or already processed by a signal)
		// or (in case of preview images) already in the DB/in the processing folder
		if (!$processedFile->isProcessed()) {
			$this->process($processedFile, $targetStorage);
		}

		// Post-process (enrich) the file
		$this->emitPostFileProcess($processedFile, $fileObject, $taskType, $configuration);

		return $processedFile;
	}

	/**
	 * Processes the file
	 *
	 * @param Resource\ProcessedFile $processedFile
	 * @param Resource\ResourceStorage $targetStorage The storage to put the processed file into
	 *
	 * @throws \RuntimeException
	 */
	protected function process(Resource\ProcessedFile $processedFile, Resource\ResourceStorage $targetStorage) {
		$targetFolder = $targetStorage->getProcessingFolder();
		if (!is_object($targetFolder)) {
			throw new \RuntimeException('Could not get processing folder for storage ' . $this->storage->getName(), 1350514301);
		}

		// We only have to trigger the file processing if the file either is new, does not exist or the
		// original file has changed since the last processing run (the last case has to trigger a reprocessing
		// even if the original file was used until now)
		if ($processedFile->isNew() || (!$processedFile->usesOriginalFile() && !$processedFile->exists()) ||
			$processedFile->isOutdated()) {

			$task = $processedFile->getTask();
			/** @var $processor Resource\Processing\LocalImageProcessor */
			$processor = Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor');
			$processor->processTask($task);

			if ($processedFile->isProcessed()) {
				/** @var $processedFileRepository Resource\ProcessedFileRepository */
				$processedFileRepository = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
				$processedFileRepository->add($processedFile);
			}
		}
	}

	/**
	 * Get the SignalSlot dispatcher
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		if (!isset($this->signalSlotDispatcher)) {
			$this->signalSlotDispatcher = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
				->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		}
		return $this->signalSlotDispatcher;
	}

	/**
	 * Emits file pre-processing signal.
	 *
	 * @param Resource\ProcessedFile $processedFile
	 * @param Resource\FileInterface $file
	 * @param string $context
	 * @param array $configuration
	 */
	protected function emitPreFileProcess(Resource\ProcessedFile $processedFile, Resource\FileInterface $file, $context, array $configuration = array()) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PreFileProcess, array($this, $this->driver, $processedFile, $file, $context, $configuration));
	}

	/**
	 * Emits file post-processing signal.
	 *
	 * @param Resource\ProcessedFile $processedFile
	 * @param Resource\FileInterface $file
	 * @param $context
	 * @param array $configuration
	 */
	protected function emitPostFileProcess(Resource\ProcessedFile $processedFile, Resource\FileInterface $file, $context, array $configuration = array()) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', self::SIGNAL_PostFileProcess, array($this, $this->driver, $processedFile, $file, $context, $configuration));
	}
}


?>