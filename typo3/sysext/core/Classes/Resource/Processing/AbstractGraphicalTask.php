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
	 * Sets parameters needed in the checksum. Can be overridden to add additional parameters to the checksum.
	 * This should include all parameters that could possibly vary between different task instances, e.g. the
	 * TYPO3 image configuration in TYPO3_CONF_VARS[GFX] for graphic processing tasks.
	 *
	 * @return array
	 */
	protected function getChecksumData() {
		return array_merge(
			parent::getChecksumData(),
			array(serialize($GLOBALS['TYPO3_CONF_VARS']['GFX']))
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

		return $this->targetFile->getNameWithoutExtension()
			. '_' . $this->getConfigurationChecksum()
			. '.' . $targetFileExtension;
	}


}

?>