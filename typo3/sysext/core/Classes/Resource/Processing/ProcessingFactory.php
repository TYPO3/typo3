<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Utility;
use \TYPO3\CMS\Core\Resource;

/**
 * Generic factory for all objects related to file processing, e.g. ProcessedFiles, Tasks and Processors.
 */
class ProcessingFactory {

	/**
	 * @var Resource\ResourceFactory
	 */
	protected $resourceFactory;

	/**
	 * Construcotr
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
	public function getProcessedFileObject(\TYPO3\CMS\Core\Resource\FileInterface $originalFileObject, $task, array $configuration) {
		/* @var \TYPO3\CMS\Core\Resource\ProcessedFileRepository $repository */
		$repository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
		// Check if this file already exists in the DB
		$processedFileObject = $repository->findOneByOriginalFileAndTaskAndConfiguration($originalFileObject, $task, $configuration);
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