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
abstract class AbstractGraphicalTask extends AbstractTask {

	/**
	 * Sets parameters needed in the checksum
	 * Can be overriden to extend parameters which ahve to be included
	 * in checksum.
	 *
	 * Example: T3_CONF_VARS[GFX] for Graphical Tasks
	 */
	protected function initializeChecksumData() {
		parent::initializeChecksumData();
		$this->checksumData[] = serialize($GLOBALS['TYPO3_CONF_VARS']['GFX']);
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

		return $this->targetFile->getNameWithoutExtension()
			. '_' . $this->getConfigurationChecksum()
			. '.' . $targetFileExtension;
	}


}

?>