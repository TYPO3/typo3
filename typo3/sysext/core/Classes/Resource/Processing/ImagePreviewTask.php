<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

/**
 *
 */
class ImagePreviewTask extends AbstractGraphicalTask {

	/**
	 * @var string
	 */
	protected $type = 'Image';

	/**
	 * @var string
	 */
	protected $name = 'Preview';

	/**
	 * @return string
	 */
	public function getTargetFileName() {
		return 'preview_' . parent::getTargetFilename();
	}

	/**
	 * Checks if the given configuration is sensible for this task, i.e. if all required parameters
	 * are given, within the boundaries and don't conflict with each other.
	 *
	 * @param array $configuration
	 * @return boolean
	 */
	protected function isValidConfiguration(array $configuration) {
		/**
		 * Checks to perform:
		 * - width and/or height given, integer values?
		 */
	}

	public function fileNeedsProcessing() {
		// TODO: Implement fileNeedsProcessing() method.

		/**
		 * Checks to perform:
		 * - width/height smaller than image, keeping aspect ratio?
		 */
	}
}

?>