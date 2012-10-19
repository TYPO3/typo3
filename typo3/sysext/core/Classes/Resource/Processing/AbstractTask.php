<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource,
    \TYPO3\CMS\Core\Utility;

/**
 * Abstract base implementation of a task.
 *
 * If you extend this class, make sure that you redefine the member variables $type and $name
 * or set them in the constructor. Otherwise your task won't be recognized by the system and several
 * things will fail.
 *
 */
abstract class AbstractTask implements Task {

	/**
	 *
	 */
	protected $checksumData = array();

	/**
	 * @var Resource\ProcessedFile
	 */
	protected $targetFile;

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var boolean
	 */
	protected $successful;

	/**
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $targetFile
	 * @param array $configuration
	 */
	public function __construct(Resource\ProcessedFile $targetFile, array $configuration) {
		$this->targetFile = $targetFile;
		$this->configuration = $configuration;
	}

	/**
	 * Sets parameters needed in the checksum
	 * Can be overriden to extend parameters which ahve to be included
	 * in checksum.
	 *
	 * Example: T3_CONF_VARS[GFX] for Graphical Tasks
	 */
	protected function initializeChecksumData() {
		$this->checksumData[] = $this->targetFile->getOriginalFile()->getUid();
		$this->checksumData[] = $this->getType() . '.' . $this->getName();
		$this->checksumData[] = serialize($this->configuration);
	}

	/**
	 * Returns the checksum
	 *
	 * @return string
	 */
	protected function getConfigurationChecksum() {
		$this->initializeChecksumData();
		return \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(implode('|', $this->checksumData));
	}

	/**
	 * Returns the filename
	 *
	 * @return string
	 */
	public function getTargetFilename() {
		return $this->targetFile->getNameWithoutExtension()
			. '_' . $this->getConfigurationChecksum()
			. '.' . $this->targetFile->getExtension();
	}

	/**
	 * Returns the name of this task
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the type of this task
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return Resource\ProcessedFile
	 */
	public function getTargetFile() {
		return $this->targetFile;
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Checks if the given configuration is sensible for this task, i.e. if all required parameters
	 * are given, within the boundaries and don't conflict with each other.
	 *
	 * @param array $configuration
	 * @return boolean
	 */
	abstract protected function isValidConfiguration(array $configuration);

	/**
	 * @return boolean
	 * @throws \LogicException If the task has not been executed already
	 */
	public function isSuccessful() {
		return $this->successful;
	}

	/**
	 * @param $resultFilePath
	 *
	 * @return void
	 */
	public function setResultFilePath($resultFilePath) {
		// TODO: Implement setResultFilePath() method.
	}

	/**
	 * @return string
	 */
	public function getResultFilePath() {
		// TODO: Implement getResultFilePath() method.
	}
}

?>