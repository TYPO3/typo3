<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

/**
 * A task is a unit of work that can be performed by a file processor. This may include multiple steps in any order,
 * details depend on the configuration of the task and the tools the processor uses.
 *
 * Each task has a type and a name. The type describes the category of the task, like "image" and "video". If your task
 * is generic or applies to multiple types of files, use "general".
 */
interface Task {

	/**
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $targetFile
	 * @param array $configuration
	 */
	public function __construct(Resource\ProcessedFile $targetFile, array $configuration);

	/**
	 * Returns the name of this task
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns the type of this task
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * @return Resource\ProcessedFile
	 */
	public function getTargetFile();

	/**
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * @return string
	 */
	public function getTargetFileName();

	/**
	 * Returns TRUE if the file has to be processed at all, such as e.g. the original file does.
	 *
	 * Note: This does not indicate if the concrete ProcessedFile attached to this task has to be (re)processed.
	 * This check is done in ProcessedFile::isOutdated(). TODO isOutdated()/needsReprocessing()?
	 *
	 * @return boolean
	 */
	public function fileNeedsProcessing();

	/**
	 * @return boolean
	 * @throws \LogicException If the task has not been executed already
	 */
	public function isSuccessful();

	/**
	 * Sets the (local) path to the processed file.
	 *
	 * @param string $resultFilePath
	 * @internal
	 */
	public function setResultFilePath($resultFilePath);

	/**
	 * @return string
	 */
	public function getResultFilePath();
}

?>