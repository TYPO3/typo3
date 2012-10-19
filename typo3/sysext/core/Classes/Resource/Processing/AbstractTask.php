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
 * TODO tasks should be registered at a central place that takes care of the naming
 *      (i.e. we don't have to define type and name here)
 */
abstract class AbstractTask implements Task {
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

	public function __construct(Resource\ProcessedFile $targetFile, $configuration) {
		$this->targetFile = $targetFile;
		$this->configuration = $configuration;
	}

	/**
	 * Returns the checksum
	 *
	 * @return string
	 */
	protected function getConfigurationChecksum() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(
			$this->targetFile->getOriginalFile()->getUid() . '|' .
			$this->getType() . '.' . $this->getName() . '|' .
			serialize($GLOBALS['TYPO3_CONF_VARS']['GFX']) . '|' . // TODO this should not be used for non-graphics tasks
			serialize($this->configuration)
		);
	}

	/**
	 * Returns the filename
	 *
	 * @return string
	 */
	public function getTargetFilename() {
		if ($this->targetFile->getOriginalFile()->getExtension() === 'jpg') {
			$targetFileExtension = 'jpg';
		} else {
			$targetFileExtension = 'png';
		}

		return $this->targetFile->getOriginalFile()->getNameWithoutExtension()
			. '_' . $this->getConfigurationChecksum()
			. '.' . $targetFileExtension;
		// TODO replace non-web-extensions by png/jpg
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
	 */
	public function isExecuted() {
		// TODO: Implement isExecuted() method.
	}

	/**
	 * @return boolean
	 * @throws \LogicException If the task has not been executed already
	 */
	public function isSuccessful() {
		return $this->successful;
	}

	/**
	 * Sets the path to the processed file.
	 *
	 * @return void
	 * @internal
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